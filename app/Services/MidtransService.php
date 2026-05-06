<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$clientKey = (string) config('services.midtrans.client_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = (bool) config('services.midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('services.midtrans.is_3ds', true);
        Config::$curlOptions = $this->buildCurlOptions();
    }

    public function createOrRefreshSnapTransaction(Payment $payment): array
    {
        $shipment = $payment->shipment()->with(['sender', 'receiver'])->firstOrFail();

        $params = [
            'transaction_details' => [
                'order_id' => $payment->gateway_order_id,
                'gross_amount' => (int) round((float) $payment->amount),
            ],
            'customer_details' => [
                'first_name' => $shipment->sender->name,
                'email' => $shipment->sender->email,
                'phone' => $shipment->sender->phone,
                'billing_address' => [
                    'first_name' => $shipment->sender->name,
                    'phone' => $shipment->sender->phone,
                    'address' => $shipment->sender->address,
                    'city' => $shipment->sender->city,
                    'country_code' => 'IDN',
                ],
                'shipping_address' => [
                    'first_name' => $shipment->receiver->name,
                    'phone' => $shipment->receiver->phone,
                    'address' => $shipment->receiver->address,
                    'city' => $shipment->receiver->city,
                    'country_code' => 'IDN',
                ],
            ],
            'item_details' => [
                [
                    'id' => 'shipment-' . $shipment->id,
                    'price' => (int) round((float) $payment->amount),
                    'quantity' => 1,
                    'name' => 'Pengiriman ' . $shipment->tracking_number,
                ],
            ],
        ];

        $response = Snap::createTransaction($params);

        return [
            'token' => $response->token,
            'redirect_url' => $response->redirect_url,
            'payload' => $params,
        ];
    }

    public function handleNotification(): Payment
    {
        $notification = new Notification();

        $payment = Payment::where('gateway_order_id', $notification->order_id)->firstOrFail();

        return $this->syncPaymentFromMidtransResponse($payment, [
            'transaction_status' => $notification->transaction_status,
            'fraud_status' => $notification->fraud_status ?? null,
            'payment_type' => $notification->payment_type ?? null,
            'transaction_id' => $notification->transaction_id ?? null,
            'status_code' => $notification->status_code ?? null,
            'gross_amount' => $notification->gross_amount ?? null,
            'payload' => json_decode(json_encode($notification), true),
        ]);
    }

    public function refreshPaymentStatus(Payment $payment): Payment
    {
        if (empty($payment->gateway_order_id)) {
            return $payment;
        }

        try {
            $response = Transaction::status($payment->gateway_order_id);
        } catch (\Exception $exception) {
            // In local/sandbox Snap flow, Midtrans can return 404 before the customer
            // selects a payment method. Treat it as still pending instead of failing the app.
            if ($exception->getCode() === 404 || str_contains($exception->getMessage(), "Transaction doesn't exist")) {
                return $payment;
            }

            Log::error('Failed to refresh Midtrans payment status.', [
                'payment_id' => $payment->id,
                'gateway_order_id' => $payment->gateway_order_id,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            throw $exception;
        }

        return $this->syncPaymentFromMidtransResponse($payment, [
            'transaction_status' => $response->transaction_status ?? null,
            'fraud_status' => $response->fraud_status ?? null,
            'payment_type' => $response->payment_type ?? null,
            'transaction_id' => $response->transaction_id ?? null,
            'status_code' => $response->status_code ?? null,
            'gross_amount' => $response->gross_amount ?? null,
            'payload' => json_decode(json_encode($response), true),
        ]);
    }

    public function syncPaymentFromMidtransResponse(Payment $payment, array $data): Payment
    {
        $transactionStatus = (string) ($data['transaction_status'] ?? '');
        $fraudStatus = $data['fraud_status'] ?? null;

        $paymentStatus = match (true) {
            in_array($transactionStatus, ['capture', 'settlement'], true) && ($fraudStatus === null || $fraudStatus === 'accept') => Payment::STATUS_PAID,
            $transactionStatus === 'pending' => Payment::STATUS_PENDING,
            in_array($transactionStatus, ['deny', 'cancel', 'failure'], true) || $fraudStatus === 'deny' => Payment::STATUS_FAILED,
            $transactionStatus === 'expire' => Payment::STATUS_EXPIRED,
            $transactionStatus === 'refund' => Payment::STATUS_REFUNDED,
            default => $payment->payment_status,
        };

        $payload = Arr::get($data, 'payload');

        $payment->update([
            'payment_method' => Payment::METHOD_MIDTRANS,
            'payment_status' => $paymentStatus,
            'midtrans_transaction_status' => $transactionStatus ?: $payment->midtrans_transaction_status,
            'gateway_transaction_id' => $data['transaction_id'] ?? $payment->gateway_transaction_id,
            'payment_channel' => $data['payment_type'] ?? $payment->payment_channel,
            'gateway_payload' => $payload,
            'paid_at' => $paymentStatus === Payment::STATUS_PAID ? ($payment->paid_at ?? now()) : $payment->paid_at,
            'payment_date' => $paymentStatus === Payment::STATUS_PAID ? ($payment->payment_date ?? now()->toDateString()) : $payment->payment_date,
            'expired_at' => $paymentStatus === Payment::STATUS_PENDING ? $payment->expired_at : $payment->expired_at,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return $payment->fresh();
    }

    public function generateGatewayOrderId(Shipment $shipment): string
    {
        return 'EXP-' . $shipment->tracking_number . '-' . now()->format('YmdHis');
    }

    private function buildCurlOptions(): array
    {
        $verifySsl = (bool) config('services.midtrans.verify_ssl', true);
        $caInfo = (string) config('services.midtrans.ca_info', '');

        if (!$verifySsl) {
            return [
                CURLOPT_HTTPHEADER => [],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ];
        }

        $curlOptions = [
            CURLOPT_HTTPHEADER => [],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if ($caInfo !== '') {
            if (!is_file($caInfo)) {
                throw new RuntimeException('MIDTRANS_CAINFO tidak ditemukan: ' . $caInfo);
            }

            $curlOptions[CURLOPT_CAINFO] = $caInfo;
        }

        return $curlOptions;
    }
}
