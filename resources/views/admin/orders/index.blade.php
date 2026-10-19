@extends('admin.layouts.app')
@section('title', 'Pesanan')

@section('content')
    <div class="bg-surface-container-low rounded-lg shadow-elev-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-highest text-on-surface-variant text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor</th>
                    <th class="px-4 py-3 font-medium">Pembeli</th>
                    <th class="px-4 py-3 font-medium">Item</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Dibuat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-on-surface">
                @forelse ($orders as $order)
                    <tr class="hover:bg-on-surface/4">
                        <td class="px-4 py-3 font-mono text-xs">{{ $order->nomor_order }}</td>
                        <td class="px-4 py-3">
                            <p>{{ $order->user?->name ?? '-' }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $order->user?->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $order->items_count }} item</td>
                        <td class="px-4 py-3 font-medium">
                            Rp {{ number_format($order->total_harga, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $tone = match ($order->status) {
                                    'dibatalkan' => 'error',
                                    'selesai' => 'success',
                                    'menunggu_pembayaran' => 'warning',
                                    default => 'info',
                                };
                            @endphp
                            @include('admin.partials.badge', ['tone' => $tone, 'text' => $order->status])
                        </td>
                        <td class="px-4 py-3 text-xs text-on-surface-variant">
                            {{ $order->created_at?->format('d M Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">
                            Belum ada pesanan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
@endsection
