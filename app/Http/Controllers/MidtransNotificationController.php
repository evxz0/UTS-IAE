<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends Controller
{
    public function __construct()
    {
        Config::$serverKey        = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction     = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized      = true;
        Config::$is3ds            = true;
        Config::$curlOptions      = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => [],
        ];
    }

    /**
     * Menerima HTTP Notification (webhook) dari Midtrans.
     * Dipanggil otomatis oleh Midtrans setiap kali status pembayaran berubah.
     *
     * Endpoint: POST /midtrans/notification
     * Perlu di-expose ke internet via ngrok agar Midtrans bisa menjangkaunya.
     */
    public function handle(Request $request)
    {
        try {
            // Gunakan Midtrans Notification class untuk parsing & verifikasi
            $notification = new Notification();

            $orderId           = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus       = $notification->fraud_status;
            $paymentType       = $notification->payment_type;
            $signatureKey      = $notification->signature_key;

            Log::info('[Midtrans Webhook] Notifikasi diterima', [
                'order_id'           => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status'       => $fraudStatus,
                'payment_type'       => $paymentType,
            ]);

            // ── Verifikasi Signature Key ──────────────────────────────────────
            // Format: SHA512(order_id + status_code + gross_amount + server_key)
            $serverKey     = env('MIDTRANS_SERVER_KEY');
            $statusCode    = $notification->status_code;
            $grossAmount   = $notification->gross_amount;
            $expectedSig   = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($signatureKey !== $expectedSig) {
                Log::warning('[Midtrans Webhook] Signature key tidak valid!', [
                    'order_id' => $orderId,
                ]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }
            // ─────────────────────────────────────────────────────────────────

            // Cari transaksi berdasarkan midtrans_order_id
            $transaction = Transaction::where('midtrans_order_id', $orderId)->first();

            if (!$transaction) {
                Log::warning('[Midtrans Webhook] Transaksi tidak ditemukan', [
                    'order_id' => $orderId,
                ]);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // ── Update Status Berdasarkan Notifikasi ──────────────────────────
            $newStatus = $this->resolveStatus($transactionStatus, $fraudStatus);

            $transaction->update(['status' => $newStatus]);

            Log::info('[Midtrans Webhook] Status transaksi diupdate', [
                'order_id'   => $orderId,
                'new_status' => $newStatus,
            ]);

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            Log::error('[Midtrans Webhook] Error memproses notifikasi', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Menentukan status internal berdasarkan status dari Midtrans.
     *
     * @param  string       $transactionStatus  Status dari Midtrans
     * @param  string|null  $fraudStatus        Fraud status dari Midtrans
     * @return string                           Status internal ('success'|'pending'|'failed')
     */
    private function resolveStatus(string $transactionStatus, ?string $fraudStatus): string
    {
        if ($transactionStatus === 'capture') {
            // Kartu kredit: cek fraud status
            return $fraudStatus === 'accept' ? 'success' : 'failed';
        }

        return match ($transactionStatus) {
            'settlement' => 'success',   // Pembayaran berhasil (QRIS, transfer, dll)
            'pending'    => 'pending',   // Menunggu pembayaran
            'deny',
            'cancel',
            'expire',
            'failure'    => 'failed',    // Pembayaran gagal/dibatalkan
            default      => 'pending',
        };
    }
}
