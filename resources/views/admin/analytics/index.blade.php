@extends('admin.layouts.app')
@section('title', 'Analitik')

@section('content')
    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        @php
            $cards = [
                ['label' => 'Pengguna', 'value' => $stats['total_users'], 'icon' => 'people', 'tone' => 'bg-primary-container text-on-primary-container'],
                ['label' => 'Toko', 'value' => $stats['total_shops'], 'icon' => 'storefront', 'tone' => 'bg-secondary-container text-on-secondary-container'],
                ['label' => 'Produk', 'value' => $stats['total_products'], 'icon' => 'inventory_2', 'tone' => 'bg-tertiary-container text-on-tertiary-container'],
                ['label' => 'Pesanan', 'value' => $stats['total_orders'], 'icon' => 'receipt_long', 'tone' => 'bg-surface-container-highest text-on-surface-variant'],
                ['label' => 'Pendapatan', 'value' => 'Rp ' . number_format($stats['total_revenue'], 0, ',', '.'), 'icon' => 'payments', 'tone' => 'bg-error-container text-on-error-container'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="bg-surface-container-low rounded-lg p-4 flex items-center gap-3 shadow-elev-1">
                <div class="w-11 h-11 rounded-full {{ $card['tone'] }} flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">{{ $card['icon'] }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-on-surface-variant truncate">{{ $card['label'] }}</p>
                    <p class="text-lg font-medium text-on-surface truncate">{{ $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">

        {{-- Grafik batang: pendapatan 6 bulan (murni CSS) --}}
        <div class="lg:col-span-2 bg-surface-container-low rounded-lg shadow-elev-1 p-4">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">monitoring</span>
                <h2 class="font-medium text-on-surface">Pendapatan 6 Bulan Terakhir</h2>
            </div>

            @php
                $maxValue = max(1, $months->max('value'));
            @endphp

            <div class="flex items-end gap-3 h-44">
                @foreach ($months as $month)
                    @php
                        $heightPct = (int) round(($month['value'] / $maxValue) * 100);
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-2 min-w-0">
                        <span class="text-[10px] text-on-surface-variant truncate w-full text-center"
                              title="Rp {{ number_format($month['value'], 0, ',', '.') }}">
                            {{ $month['value'] > 0 ? number_format($month['value'] / 1000, 0, ',', '.') . 'k' : '0' }}
                        </span>
                        <div class="w-full rounded-t-md bg-primary/85 transition-all duration-300"
                             style="height: {{ max(2, $heightPct) }}%"
                             title="Rp {{ number_format($month['value'], 0, ',', '.') }}"></div>
                        <span class="text-[11px] text-on-surface-variant truncate w-full text-center">
                            {{ $month['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Distribusi status pesanan --}}
        <div class="bg-surface-container-low rounded-lg shadow-elev-1 p-4">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">donut_small</span>
                <h2 class="font-medium text-on-surface">Status Pesanan</h2>
            </div>

            @php
                $totalOrders = max(1, $ordersByStatus->sum());
                $statusTone = [
                    'menunggu_pembayaran' => 'bg-primary',
                    'diproses' => 'bg-secondary',
                    'dikirim' => 'bg-tertiary',
                    'selesai' => 'bg-outline',
                    'dibatalkan' => 'bg-error',
                ];
            @endphp

            <ul class="space-y-3">
                @forelse ($ordersByStatus as $status => $total)
                    <li>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-on-surface-variant">{{ $status }}</span>
                            <span class="font-medium text-on-surface">
                                {{ $total }}
                                <span class="text-xs font-normal text-on-surface-variant">
                                    ({{ round($total / $totalOrders * 100) }}%)
                                </span>
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-surface-container-highest overflow-hidden">
                            <div class="h-full rounded-full {{ $statusTone[$status] ?? 'bg-outline' }}"
                                 style="width: {{ round($total / $totalOrders * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-on-surface-variant text-center py-4">Belum ada pesanan.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">

        {{-- Produk terlaris --}}
        <div class="bg-surface-container-low rounded-lg shadow-elev-1">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-outline-variant">
                <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">trophy</span>
                <h2 class="font-medium text-on-surface">Produk Terlaris</h2>
            </div>
            @if ($topProducts->isEmpty())
                <p class="px-4 py-6 text-sm text-on-surface-variant text-center">Belum ada penjualan.</p>
            @else
                <ul class="divide-y divide-outline-variant">
                    @foreach ($topProducts as $index => $product)
                        <li class="px-4 py-3 flex items-center gap-3 text-sm">
                            <span class="w-6 h-6 rounded-full bg-secondary-container text-on-secondary-container
                                         flex items-center justify-center text-xs font-medium shrink-0">
                                {{ $index + 1 }}
                            </span>
                            <span class="flex-1 min-w-0 truncate text-on-surface">{{ $product->nama_produk }}</span>
                            <span class="text-on-surface-variant whitespace-nowrap">{{ $product->terjual }} terjual</span>
                            <span class="font-medium text-on-surface whitespace-nowrap">
                                Rp {{ number_format($product->pendapatan, 0, ',', '.') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Pengguna terbaru --}}
        <div class="bg-surface-container-low rounded-lg shadow-elev-1">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-outline-variant">
                <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">person_add</span>
                <h2 class="font-medium text-on-surface">Pengguna Terbaru</h2>
            </div>
            <ul class="divide-y divide-outline-variant">
                @foreach ($recentUsers as $user)
                    <li class="px-4 py-3 flex items-center gap-3 text-sm">
                        <div class="w-8 h-8 rounded-full bg-tertiary-container text-on-tertiary-container
                                    flex items-center justify-center text-xs font-medium shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-on-surface truncate">{{ $user->name }}</p>
                            <p class="text-xs text-on-surface-variant truncate">{{ $user->email }}</p>
                        </div>
                        <span class="text-xs text-on-surface-variant whitespace-nowrap">
                            {{ $user->created_at?->diffForHumans(short: true) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Pesanan terbaru --}}
    <div class="bg-surface-container-low rounded-lg shadow-elev-1 mt-6">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-outline-variant">
            <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">history</span>
            <h2 class="font-medium text-on-surface">Pesanan Terbaru</h2>
        </div>
        @if ($recentOrders->isEmpty())
            <p class="px-4 py-6 text-sm text-on-surface-variant text-center">Belum ada pesanan.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-container-highest text-on-surface-variant text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nomor</th>
                            <th class="px-4 py-3 font-medium">Pembeli</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-on-surface">
                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-on-surface/4">
                                <td class="px-4 py-3 font-mono text-xs">{{ $order->nomor_order }}</td>
                                <td class="px-4 py-3">{{ $order->user?->name ?? '-' }}</td>
                                <td class="px-4 py-3">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $tone = $order->status === 'dibatalkan' ? 'error' : ($order->status === 'menunggu_pembayaran' ? 'warning' : 'info');
                                    @endphp
                                    @include('admin.partials.badge', ['tone' => $tone, 'text' => $order->status])
                                </td>
                                <td class="px-4 py-3 text-xs text-on-surface-variant">
                                    {{ $order->created_at?->format('d M H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
