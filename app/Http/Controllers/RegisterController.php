<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    private const OTP_SESSION_KEY = 'customer_register_otp';
    private const OTP_EXPIRY_MINUTES = 10;
    private const OTP_RESEND_COOLDOWN_SECONDS = 60;

    public function create()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        return view('auth.register');
    }

    public function store(Request $request)
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:customers,email',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $this->forgetPendingRegistration($request, true);

        $photoName = null;

        if ($request->hasFile('photo')) {
            $photoName = Uploads::storePublic($request->file('photo'), 'register-temp', 'customer');
        }

        $otpCode = $this->generateOtpCode();
        $pendingRegistration = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'address' => $validated['address'],
            'city' => $validated['city'],
            'phone' => $validated['phone'],
            'photo' => $photoName,
            'otp_hash' => Hash::make($otpCode),
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES)->timestamp,
            'otp_resend_available_at' => now()->addSeconds(self::OTP_RESEND_COOLDOWN_SECONDS)->timestamp,
        ];

        $request->session()->put(self::OTP_SESSION_KEY, $pendingRegistration);

        try {
            $this->sendOtpEmail($pendingRegistration['email'], $pendingRegistration['name'], $otpCode);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('auth.register.verify')
                ->with('warning', $this->mailFailureMessage($exception));
        }

        return redirect()
            ->route('auth.register.verify')
            ->with('success', 'Kode OTP sudah dikirim ke email yang Anda daftarkan.');
    }

    public function showOtpForm(Request $request)
    {
        $pendingRegistration = $this->pendingRegistration($request);

        if (!$pendingRegistration) {
            return redirect()->route('auth.register')->with('warning', 'Silakan isi form register terlebih dahulu.');
        }

        return view('auth.register_verify_otp', [
            'email' => $pendingRegistration['email'],
            'expiresAt' => $pendingRegistration['otp_expires_at'] ?? null,
            'resendAvailableAt' => $pendingRegistration['otp_resend_available_at'] ?? null,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard.index');
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('home.index');
        }

        $pendingRegistration = $this->pendingRegistration($request);

        if (!$pendingRegistration) {
            return redirect()->route('auth.register')->with('warning', 'Sesi OTP sudah habis. Silakan daftar ulang.');
        }

        $validated = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (now()->timestamp > (int) ($pendingRegistration['otp_expires_at'] ?? 0)) {
            return back()->withErrors([
                'otp' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang OTP.',
            ])->withInput();
        }

        if (!Hash::check($validated['otp'], $pendingRegistration['otp_hash'] ?? '')) {
            return back()->withErrors([
                'otp' => 'Kode OTP tidak valid.',
            ])->withInput();
        }

        if (Customer::where('email', $pendingRegistration['email'])->exists()) {
            $this->forgetPendingRegistration($request, true);

            return redirect()->route('login')->with('warning', 'Email tersebut sudah terdaftar. Silakan login.');
        }

        $finalPhoto = null;
        if (!empty($pendingRegistration['photo'])) {
            $finalPhoto = Uploads::movePublic('register-temp', 'customers', $pendingRegistration['photo']);
        }

        DB::transaction(function () use ($pendingRegistration, $finalPhoto): void {
            Customer::create([
                'name' => $pendingRegistration['name'],
                'email' => $pendingRegistration['email'],
                'password' => $pendingRegistration['password'],
                'address' => $pendingRegistration['address'],
                'city' => $pendingRegistration['city'],
                'phone' => $pendingRegistration['phone'],
                'photo' => $finalPhoto,
                'email_verified_at' => now(),
            ]);
        });

        $this->forgetPendingRegistration($request, false);

        return redirect()->route('login')->with('success', 'Register berhasil dan email sudah terverifikasi. Silakan login.');
    }

    public function resendOtp(Request $request)
    {
        $pendingRegistration = $this->pendingRegistration($request);

        if (!$pendingRegistration) {
            return redirect()->route('auth.register')->with('warning', 'Sesi OTP sudah habis. Silakan daftar ulang.');
        }

        $resendAvailableAt = (int) ($pendingRegistration['otp_resend_available_at'] ?? 0);
        if (now()->timestamp < $resendAvailableAt) {
            return back()->with('warning', 'Tunggu sebentar sebelum mengirim ulang OTP.');
        }

        $otpCode = $this->generateOtpCode();
        $pendingRegistration['otp_hash'] = Hash::make($otpCode);
        $pendingRegistration['otp_expires_at'] = now()->addMinutes(self::OTP_EXPIRY_MINUTES)->timestamp;
        $pendingRegistration['otp_resend_available_at'] = now()->addSeconds(self::OTP_RESEND_COOLDOWN_SECONDS)->timestamp;
        $request->session()->put(self::OTP_SESSION_KEY, $pendingRegistration);

        try {
            $this->sendOtpEmail($pendingRegistration['email'], $pendingRegistration['name'], $otpCode);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('warning', $this->mailFailureMessage($exception));
        }

        return back()->with('success', 'Kode OTP baru sudah dikirim ke email Anda.');
    }

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendOtpEmail(string $email, string $name, string $otpCode): void
    {
        if (config('mail.default') === 'smtp' && blank(config('mail.mailers.smtp.username'))) {
            throw new \RuntimeException('MAIL_USERNAME belum diisi untuk pengiriman OTP.');
        }

        if (blank(config('mail.from.address'))) {
            throw new \RuntimeException('MAIL_FROM_ADDRESS belum diisi untuk pengiriman OTP.');
        }

        Mail::send('emails.customer_register_otp', [
            'name' => $name,
            'otpCode' => $otpCode,
            'expiryMinutes' => self::OTP_EXPIRY_MINUTES,
        ], function ($message) use ($email, $name): void {
            $message->to($email, $name)
                ->subject('Kode OTP Verifikasi Register Ekspedisi Online');
        });
    }

    private function pendingRegistration(Request $request): ?array
    {
        $pendingRegistration = $request->session()->get(self::OTP_SESSION_KEY);

        return is_array($pendingRegistration) ? $pendingRegistration : null;
    }

    private function forgetPendingRegistration(Request $request, bool $deleteTempPhoto): void
    {
        $pendingRegistration = $this->pendingRegistration($request);

        if ($deleteTempPhoto && !empty($pendingRegistration['photo'])) {
            Uploads::deletePublic('register-temp', $pendingRegistration['photo']);
        }

        $request->session()->forget(self::OTP_SESSION_KEY);
    }

    private function mailFailureMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'MAIL_USERNAME belum diisi')) {
            return 'OTP belum bisa dikirim karena email pengirim belum diisi. Isi MAIL_USERNAME dan MAIL_FROM_ADDRESS dengan Gmail pengirim yang sama, lalu klik kirim ulang OTP.';
        }

        if (str_contains($message, 'MAIL_FROM_ADDRESS belum diisi')) {
            return 'OTP belum bisa dikirim karena MAIL_FROM_ADDRESS masih kosong. Isi dengan email Gmail pengirim, lalu klik kirim ulang OTP.';
        }

        return 'OTP gagal dikirim. Periksa konfigurasi email lalu coba lagi.';
    }
}
