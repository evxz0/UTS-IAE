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

        Config::$curlOptions = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [],
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
                    'callbacks' => [
                        'finish' => env('APP_URL') . '/pos',
                    ],
                ];

                try {
                    $snapToken = Snap::getSnapToken($params);
                    $transaction->update(['midtrans_snap_token' => $snapToken]);

                    if (!Config::$isProduction) {
                        $simulated = $this->simulateMidtransPayment($orderId);
                        if ($simulated) {
                            $transaction->update(['status' => 'success']);
                        }
                    }

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

    private function simulateMidtransPayment(string $orderId): bool
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $auth = base64_encode($serverKey . ':');

        $url = "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

        try {
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
