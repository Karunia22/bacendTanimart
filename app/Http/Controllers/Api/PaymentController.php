<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Membuat instruksi pembayaran simulasi.
     */
    public function store(
        Request $request,
        Order $order
    ): JsonResponse {
        $validated = $request->validate([
            'metode' => [
                'required',
                Rule::in(['transfer', 'qris']),
            ],
            'bank' => [
                'exclude_unless:metode,transfer',
                'required',
                Rule::in(['BCA', 'BRI', 'BNI', 'MANDIRI']),
            ],
        ]);

        $result = DB::transaction(function () use (
            $request,
            $order,
            $validated
        ) {
            // Kunci pesanan agar proses pembayaran tidak berjalan
            // bersamaan dengan perubahan status pesanan lainnya.
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan pesanan milik pengguna yang sedang login.
            if ((int) $lockedOrder->user_id !== (int) $request->user()->id) {
                return [
                    'error' => 'Anda tidak memiliki akses ke pesanan ini.',
                    'status' => 403,
                ];
            }

            // Pesanan harus menunggu pembayaran.
            if ($lockedOrder->status !== 'menunggu_pembayaran') {
                return [
                    'error' => 'Pesanan tidak dapat dibayar.',
                    'status' => 422,
                ];
            }

            // Batas pembayaran adalah 72 jam sejak pesanan dibuat.
            if (
                $lockedOrder->created_at->lte(now()->subMinute(15))
            ) {
                return [
                    'error' => 'Batas pembayaran telah berakhir.',
                    'status' => 422,
                ];
            }

            // Satu pesanan hanya boleh memiliki satu pembayaran.
            if ($lockedOrder->payment()->exists()) {
                return [
                    'error' => 'Pesanan ini sudah memiliki pembayaran.',
                    'status' => 422,
                ];
            }

            $metode = $validated['metode'];

            $payment = $lockedOrder->payment()->create([
                'metode' => $metode,
                'bank' => $metode === 'transfer'
                    ? $validated['bank']
                    : null,
                'jumlah' => $lockedOrder->total_harga,
                'status' => 'menunggu',
                'referensi' => 'DEMO-' . strtoupper(
                    bin2hex(random_bytes(6))
                ),
            ]);

            return [
                'payment' => $payment,
            ];
        }, 3);

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], $result['status']);
        }

        $payment = $result['payment'];

        // Semua instruksi di bawah ini hanya untuk demonstrasi.
        $instructions = $payment->metode === 'transfer'
            ? [
                'bank' => $payment->bank,
                'nomor_rekening' => null,
                'nama_penerima' => 'TaniMart',
                'petunjuk' => 'Silakan periksa kembali detail pesanan '
                    . 'Anda. Pembayaran ini belum diproses melalui '
                    . 'rekening bank.',
            ]
        : [
            'qr_code' => $payment->referensi,
            'petunjuk' => 'Pindai kode QR untuk melihat referensi '
                . 'transaksi. Kode ini digunakan untuk demonstrasi '
                . 'dan tidak dapat digunakan untuk melakukan '
                . 'pembayaran QRIS.',
        ];

        return response()->json([
            'message' => 'Instruksi pembayaran simulasi berhasil dibuat.',
            'simulation' => true,
            'payment' => $payment,
            'instructions' => $instructions,
            'expires_at' => $order->created_at
                ->copy()
                ->addMinutes(15)
                ->toISOString(),
        ], 201);
    }

    /**
     * Simulasi pembayaran berhasil untuk keperluan demo.
     *
     * Endpoint ini bukan konfirmasi pembayaran sungguhan.
     */
    public function simulateSuccess(
        Request $request,
        Order $order
    ): JsonResponse {
        $result = DB::transaction(function () use (
            $request,
            $order
        ) {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedOrder->user_id !== (int) $request->user()->id) {
                return [
                    'error' => 'Anda tidak memiliki akses ke pesanan ini.',
                    'status' => 403,
                ];
            }

            $payment = $lockedOrder->payment()
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return [
                    'error' => 'Instruksi pembayaran belum dibuat.',
                    'status' => 404,
                ];
            }

            if (
                $lockedOrder->status !== 'menunggu_pembayaran'
                || $payment->status !== 'menunggu'
            ) {
                return [
                    'error' => 'Pembayaran tidak dapat dikonfirmasi.',
                    'status' => 422,
                ];
            }

            if (
                $lockedOrder->created_at->lte(now()->subMinutes(15))
            ) {
                return [
                    'error' => 'Batas pembayaran telah berakhir.',
                    'status' => 422,
                ];
            }

            $payment->update([
                'status' => 'berhasil',
                'dibayar_pada' => now(),
            ]);

            $lockedOrder->update([
                'status' => 'diproses',
            ]);

            return [
                'payment' => $payment->fresh(),
                'order' => $lockedOrder->fresh(),
            ];
        }, 3);

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'message' => 'Simulasi pembayaran berhasil.',
            'simulation' => true,
            'payment' => $result['payment'],
            'order' => $result['order'],
        ]);
    }

    /**
     * Menampilkan detail pembayaran.
     */
    public function show(
        Request $request,
        Order $order
    ): JsonResponse {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke pesanan ini.',
            ], 403);
        }

        $payment = $order->payment;

        if (! $payment) {
            return response()->json([
                'message' => 'Pembayaran belum dibuat.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail pembayaran.',
            'payment' => $payment,
        ]);
    }
}
