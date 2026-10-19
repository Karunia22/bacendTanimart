@extends('admin.layouts.app')
@section('title', 'Produk')

@section('content')
    <div class="bg-surface-container-low rounded-lg shadow-elev-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-highest text-on-surface-variant text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Produk</th>
                    <th class="px-4 py-3 font-medium">Penjual</th>
                    <th class="px-4 py-3 font-medium">Harga</th>
                    <th class="px-4 py-3 font-medium">Stok</th>
                    <th class="px-4 py-3 font-medium">Berat</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Dibuat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-on-surface">
                @forelse ($products as $product)
                    <tr class="hover:bg-on-surface/4">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-surface-container-highest
                                            flex items-center justify-center shrink-0 overflow-hidden">
                                    @if ($product->images->first())
                                        <img src="{{ $product->images->first()->url ?? $product->images->first()->image_path ?? '' }}"
                                             alt="{{ $product->nama_produk }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <span class="material-symbols-outlined text-on-surface-variant" aria-hidden="true">image</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium truncate max-w-[14rem]">{{ $product->nama_produk }}</p>
                                    <p class="text-xs text-on-surface-variant">ID #{{ $product->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $product->seller?->name ?? '-' }}</td>
                        <td class="px-4 py-3 font-medium">
                            Rp {{ number_format($product->harga, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $product->stok <= 0 ? 'text-error font-medium' : '' }}">
                                {{ $product->stok }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $product->berat }} g</td>
                        <td class="px-4 py-3">
                            @php $tone = $product->status === 'aktif' ? 'success' : 'neutral'; @endphp
                            @include('admin.partials.badge', ['tone' => $tone, 'text' => $product->status])
                        </td>
                        <td class="px-4 py-3 text-xs text-on-surface-variant">
                            {{ $product->created_at?->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-on-surface-variant">
                            Belum ada produk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
