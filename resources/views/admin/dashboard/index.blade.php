@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
{{-- Kartu statistik M3 --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

    <div class="bg-surface-container-low rounded-lg p-4 flex items-center gap-4 shadow-elev-1">
        <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-container
                        flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]" aria-hidden="true">people</span>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-on-surface-variant truncate">Pengguna</p>
            <p class="text-2xl font-normal text-on-surface">{{ $stats['users'] }}</p>
        </div>
    </div>

    <div class="bg-surface-container-low rounded-lg p-4 flex items-center gap-4 shadow-elev-1">
        <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-container
                        flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]" aria-hidden="true">storefront</span>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-on-surface-variant truncate">Toko</p>
            <p class="text-2xl font-normal text-on-surface">{{ $stats['shops'] }}</p>
        </div>
    </div>

    <div class="bg-surface-container-low rounded-lg p-4 flex items-center gap-4 shadow-elev-1">
        <div class="w-12 h-12 rounded-full bg-tertiary-container text-on-tertiary-container
                        flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]" aria-hidden="true">inventory_2</span>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-on-surface-variant truncate">Produk</p>
            <p class="text-2xl font-normal text-on-surface">{{ $stats['products'] }}</p>
        </div>
    </div>

    <div class="bg-surface-container-low rounded-lg p-4 flex items-center gap-4 shadow-elev-1">
        <div class="w-12 h-12 rounded-full bg-error-container text-on-error-container
                        flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[24px]" aria-hidden="true">receipt_long</span>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-on-surface-variant truncate">Pesanan</p>
            <p class="text-2xl font-normal text-on-surface">{{ $stats['orders'] }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">

    {{-- Pengajuan menunggu tinjauan --}}
    <div class="lg:col-span-1 bg-surface-container-low rounded-lg shadow-elev-1">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-outline-variant">
            <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">how_to_reg</span>
            <h2 class="font-medium text-on-surface">Pengajuan Penjual</h2>
            <span class="ml-auto inline-flex items-center h-6 px-2 rounded-full text-xs font-medium
                             bg-primary-container text-on-primary-container">
                {{ $stats['pending_applications'] }} menunggu
            </span>
        </div>

        @if ($pendingApplications->isEmpty())
        <p class="px-4 py-6 text-sm text-on-surface-variant text-center">
            Tidak ada pengajuan yang menunggu tinjauan.
        </p>
        @else
        <ul class="divide-y divide-outline-variant">
            @foreach ($pendingApplications as $application)
            <li class="px-4 py-3 flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-on-surface truncate">
                        {{ $application->nama_toko }}
                    </p>
                    <p class="text-xs text-on-surface-variant truncate">
                        {{ $application->user?->name }} · {{ $application->user?->email }}
                    </p>
                </div>
                <a href="{{ route('admin.seller-applications.index') }}"
                    class="text-sm font-medium text-primary hover:underline whitespace-nowrap">
                    Tinjau
                </a>
            </li>
            @endforeach
        </ul>
        @endif
    </div>

    {{-- Pesanan terbaru --}}
    <div class="lg:col-span-2 bg-surface-container-low rounded-lg shadow-elev-1">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-outline-variant">
            <span class="material-symbols-outlined text-[20px] text-primary" aria-hidden="true">receipt_long</span>
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
                            $tone = match ($order->status) {
                            'dibatalkan' => 'error',
                            'selesai' => 'success',
                            default => 'info',
                            };
                            @endphp
                            <span class="inline-flex items-center h-6 px-2 rounded-full text-xs font-medium
@php
                                                    $cls = match($tone){ 'error'=>'bg-error-container text-on-error-container', 'success'=>'bg-tertiary-container text-on-tertiary-container', default=>'bg-secondary-container text-on-secondary-container' };
                                                    @endphp
                                                    {{ $cls }}">
                                {{ $order->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
