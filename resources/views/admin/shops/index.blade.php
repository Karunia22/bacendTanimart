@extends('admin.layouts.app')
@section('title', 'Toko')

@section('content')
    <div class="bg-surface-container-low rounded-lg shadow-elev-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-highest text-on-surface-variant text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Toko</th>
                    <th class="px-4 py-3 font-medium">Pemilik</th>
                    <th class="px-4 py-3 font-medium">Telepon</th>
                    <th class="px-4 py-3 font-medium">Produk</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Dibuat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-on-surface">
                @forelse ($shops as $shop)
                    <tr class="hover:bg-on-surface/4">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $shop->nama_toko }}</p>
                            <p class="text-xs text-on-surface-variant max-w-xs truncate">
                                {{ $shop->deskripsi ?: '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $shop->user?->name ?? '-' }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $shop->user?->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $shop->no_telepon ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $shop->products_count }}</td>
                        <td class="px-4 py-3">
                            @php $tone = $shop->status === 'aktif' ? 'success' : 'neutral'; @endphp
                            @include('admin.partials.badge', ['tone' => $tone, 'text' => $shop->status])
                        </td>
                        <td class="px-4 py-3 text-xs text-on-surface-variant">
                            {{ $shop->created_at?->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">
                            Belum ada toko.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $shops->links() }}
    </div>
@endsection
