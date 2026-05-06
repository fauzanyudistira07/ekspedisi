<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>OTP Register Ekspedisi Online</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;color:#1f2937;padding:24px;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e5e7eb;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#6b7280;margin-bottom:12px;">Verifikasi Register</div>
        <h1 style="margin:0 0 12px;font-size:28px;line-height:1.2;color:#0f172a;">Ekspedisi Online</h1>
        <p style="margin:0 0 16px;">Halo {{ $name }},</p>
        <p style="margin:0 0 20px;">Gunakan kode OTP berikut untuk menyelesaikan pendaftaran akun customer Anda:</p>
        <div style="font-size:36px;font-weight:700;letter-spacing:8px;text-align:center;background:#eff6ff;color:#1d4ed8;padding:18px 20px;border-radius:12px;margin-bottom:20px;">
            {{ $otpCode }}
        </div>
        <p style="margin:0 0 10px;">Kode ini berlaku selama <strong>{{ $expiryMinutes }} menit</strong>.</p>
        <p style="margin:0;color:#6b7280;">Jika Anda tidak merasa melakukan pendaftaran, abaikan email ini.</p>
    </div>
</body>
</html>
