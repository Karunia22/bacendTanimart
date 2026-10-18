<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:expire-unpaid-orders')]
#[Description('Membatalkan pesanan yang belum dibayar setelah 15 menit dan mengembalikan stok.')]
class ExpireUnpaidOrders extends Command
{
    public function handle(): int
    {
        $batasWaktu = now()->subMinutes(15);
        $jumlahDibatalkan = 0;

        Order::query()
            ->where('status', 'menunggu_pembayaran')
            ->where('created_at', '<=', $batasWaktu)
            ->select('id')
            ->chunkById(100, function ($orders) use (&$jumlahDibatalkan) {
                foreach ($orders as $order) {
                    $dibatalkan = DB::transaction(function () use ($order) {
                        $lockedOrder = Order::query()
                            ->whereKey($order->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            ! $lockedOrder
                            || $lockedOrder->status !== 'menunggu_pembayaran'
                            || $lockedOrder->created_at->gt(now()->subMinutes(15))
                        ) {
                            return false;
                        }

                        $payment = $lockedOrder->payment()
                            ->lockForUpdate()
                            ->first();

                        if ($payment && $payment->status === 'berhasil') {
                            return false;
                        }

                        $items = $lockedOrder->items()
                            ->orderBy('product_id')
                            ->get();

                        foreach ($items as $item) {
                            $product = Product::query()
                                ->whereKey($item->product_id)
                                ->lockForUpdate()
                                ->first();

                            if ($product) {
                                $product->increment('stok', $item->jumlah);
                            }
                        }

                        if ($payment && $payment->status === 'menunggu') {
                            $payment->update([
                                'status' => 'dibatalkan',
                            ]);
                        }

                        $lockedOrder->update([
                            'status' => 'dibatalkan',
                        ]);

                        return true;
                    }, 3);

                    if ($dibatalkan) {
                        $jumlahDibatalkan++;
                    }
                }
            });

        $this->info(
            "Selesai. {$jumlahDibatalkan} pesanan kedaluwarsa dibatalkan."
        );

        return self::SUCCESS;
    }
}
