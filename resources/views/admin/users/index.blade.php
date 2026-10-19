@extends('admin.layouts.app')
@section('title', 'Pengguna')

@section('content')
    <div class="bg-surface-container-low rounded-lg shadow-elev-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-highest text-on-surface-variant text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Pesanan</th>
                    <th class="px-4 py-3 font-medium">Toko</th>
                    <th class="px-4 py-3 font-medium">Terdaftar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-on-surface">
                @forelse ($users as $user)
                    <tr class="hover:bg-on-surface/4">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-secondary-container text-on-secondary-container
                                            flex items-center justify-center text-sm font-medium shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <span class="font-medium">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                                                        @php
                                        $tone = $role->name === 'admin' ? 'error' : ($role->name === 'seller' ? 'success' : 'info');
                                    @endphp
                                    @include('admin.partials.badge', ['tone' => $tone, 'text' => $role->name])
                                @endforeach
                                @if ($user->roles->isEmpty())
                                    <span class="text-xs text-on-surface-variant">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $user->orders_count }}</td>
                        <td class="px-4 py-3">
                            @if ($user->shop)
                                <span class="text-xs">{{ $user->shop->nama_toko }}</span>
                            @else
                                <span class="text-xs text-on-surface-variant">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-on-surface-variant">
                            {{ $user->created_at?->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">
                            Belum ada pengguna.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
@endsection
