<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Fix SSL certificate error on local/Windows development
        // Also include CURLOPT_HTTPHEADER (constant value: 10023) to prevent
        // PHP 8 "Undefined array key 10023" bug in midtrans-php library (ApiRequestor.php:117)
        Config::$curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [],   // Prevent PHP 8 undefined key error
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cart' => 'required|array',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,qris,transfer'
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $subtotal = 0;

            // Calculate securely on server
            foreach ($validated['cart'] as $item) {
                $product = Product::find($item['id']);
                $subtotal += $product->price * $item['qty'];
            }

            $tax = $subtotal * 0.11;
            $totalAmount = $subtotal + $tax;
            $orderId = 'INV-' . time() . '-' . rand(1000, 9999);

            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'tax_amount' => $tax,
                'discount_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'status' => $validated['payment_method'] === 'cash' ? 'success' : 'pending',
                'midtrans_order_id' => $orderId
            ]);

            foreach ($validated['cart'] as $item) {
                $product = Product::find($item['id']);
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $product->price * $item['qty']
                ]);

                // Deduct stock
                $product->decrement('stock', $item['qty']);
            }

            if ($validated['payment_method'] !== 'cash') {
                $params = [
                    'transaction_details' => [
                        'order_id'     => $orderId,
                        'gross_amount' => (int) $totalAmount,
                    ],
                    'customer_details' => [
                        'first_name' => auth()->user()->name,
                        'email'      => auth()->user()->email,
                    ],
                    // ── Notification URL ──────────────────────────────────────
                    // Midtrans akan mengirim HTTP POST ke URL ini setiap kali
                    // status pembayaran berubah (settlement, expire, dll).
                    // Di lokal: gunakan ngrok URL (https://<ngrok-id>.ngrok-free.app)
                    // Di production: gunakan APP_URL production kamu
                    'callbacks' => [
                        'finish' => env('APP_URL') . '/pos',
                    ],
                ];

                try {
                    $snapToken = Snap::getSnapToken($params);
                    $transaction->update(['midtrans_snap_token' => $snapToken]);

                    // ─── Sandbox Auto-Simulate Payment ───────────────────────
                    // Di sandbox, QRIS tidak bisa di-scan secara nyata.
                    // Kita panggil Midtrans Simulate API agar status langsung
                    // berubah ke 'settlement' tanpa intervensi manual.
                    if (!Config::$isProduction) {
                        $simulated = $this->simulateMidtransPayment($orderId);
                        if ($simulated) {
                            $transaction->update(['status' => 'success']);
                        }
                    }
                    // ─────────────────────────────────────────────────────────

                    return response()->json([
                        'success' => true,
                        'snap_token' => $snapToken,
                        'transaction_id' => $transaction->id,
                        'payment_status' => !Config::$isProduction ? 'auto-settled (sandbox)' : 'pending',
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cash payment successful',
                'transaction_id' => $transaction->id
            ]);
        });
    }

    /**
     * Memanggil Midtrans Sandbox Simulate Payment API.
     * Mengubah status transaksi dari 'pending' → 'settlement' secara programatik.
     * HANYA digunakan di environment sandbox (MIDTRANS_IS_PRODUCTION=false).
     *
     * Dokumentasi: https://docs.midtrans.com/reference/status-cycle-and-action
     *
     * @param  string $orderId  Midtrans order_id yang ingin disimulasikan
     * @return bool             true jika simulasi berhasil, false jika gagal
     */
    private function simulateMidtransPayment(string $orderId): bool
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $auth = base64_encode($serverKey . ':');

        $url = "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

        try {
            // Langkah 1: Ambil status transaksi saat ini
            $statusResponse = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $auth,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            Log::info('[Midtrans Sandbox] Status transaksi', [
                'order_id' => $orderId,
                'response' => $statusResponse->json(),
            ]);

            // Langkah 2: Panggil endpoint simulasi untuk force-settle
            // Endpoint ini hanya tersedia di sandbox Midtrans
            $simulateUrl = "https://api.sandbox.midtrans.com/v2/{$orderId}/status/simulate";

            $simulateResponse = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($simulateUrl, [
                    'transaction_status' => 'settlement',
                ]);

            Log::info('[Midtrans Sandbox] Simulasi pembayaran', [
                'order_id' => $orderId,
                'simulate_status' => $simulateResponse->status(),
                'simulate_body' => $simulateResponse->json(),
            ]);

            // Simulasi dianggap berhasil jika response 2xx
            return $simulateResponse->successful();

        } catch (\Exception $e) {
            Log::error('[Midtrans Sandbox] Gagal simulasi pembayaran', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
