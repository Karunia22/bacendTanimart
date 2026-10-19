@extends('admin.layouts.app')
@section('title', 'Pengajuan Penjual')

@section('content')

    {{-- Toolbar: filter status --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @include('admin.partials.chip', ['active' => $status === 'all', 'href' => route('admin.seller-applications.index', ['status' => 'all']), 'text' => 'Semua'])
        @include('admin.partials.chip', ['active' => $status === 'pending', 'href' => route('admin.seller-applications.index', ['status' => 'pending']), 'text' => 'Menunggu'])
        @include('admin.partials.chip', ['active' => $status === 'approved', 'href' => route('admin.seller-applications.index', ['status' => 'approved']), 'text' => 'Disetujui'])
        @include('admin.partials.chip', ['active' => $status === 'rejected', 'href' => route('admin.seller-applications.index', ['status' => 'rejected']), 'text' => 'Ditolak'])
    </div>

    <div class="bg-surface-container-low rounded-lg shadow-elev-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-highest text-on-surface-variant text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">Pemohon</th>
                    <th class="px-4 py-3 font-medium">Nama Toko</th>
                    <th class="px-4 py-3 font-medium">Telepon</th>
                    <th class="px-4 py-3 font-medium">Diajukan</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant text-on-surface">
                @forelse ($applications as $application)
                    <tr class="hover:bg-on-surface/4">
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $application->user?->name ?? '-' }}</p>
                            <p class="text-xs text-on-surface-variant">{{ $application->user?->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $application->nama_toko }}</p>
                            <p class="text-xs text-on-surface-variant max-w-xs truncate">
                                {{ $application->deskripsi_toko ?: '—' }}
                            </p>
                        </td>
                        <td class="px-4 py-3">{{ $application->no_telepon }}</td>
                        <td class="px-4 py-3 text-xs text-on-surface-variant">
                            {{ $application->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $tone = match ($application->status_pengajuan) {
                                    'approved' => 'success',
                                    'rejected' => 'error',
                                    default => 'warning',
                                };
                            @endphp
                            @include('admin.partials.badge', ['tone' => $tone, 'text' => $application->status_pengajuan])
                            @if ($application->catatan_admin)
                                <p class="mt-1 text-xs text-on-surface-variant max-w-[14rem]"
                                   title="{{ $application->catatan_admin }}">
                                    Catatan: {{ \Illuminate\Support\Str::limit($application->catatan_admin, 60) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if ($application->status_pengajuan === 'pending')
                                <button type="button"
                                    onclick="document.getElementById('dlg-reject-{{ $application->id }}').showModal()"
                                    class="h-9 px-4 rounded-full border border-outline text-error text-sm font-medium
                                           hover:bg-error-container/40 transition-colors">
                                    Tolak
                                </button>
                                <button type="button"
                                    onclick="document.getElementById('dlg-approve-{{ $application->id }}').showModal()"
                                    class="h-9 px-4 rounded-full bg-primary text-on-primary text-sm font-medium
                                           hover:shadow-elev-1 transition-shadow ml-2">
                                    Setujui
                                </button>
                            @else
                                <span class="text-xs text-on-surface-variant">
                                    Ditinjau {{ $application->reviewer?->name ?? '-' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">
                            Tidak ada pengajuan pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $applications->links() }}
    </div>

    {{-- Dialog + tombol form disembunyikan, per baris pengajuan --}}
    @foreach ($applications->items() as $application)
        @if ($application->status_pengajuan === 'pending')

            {{-- Dialog Setujui --}}
            <dialog id="dlg-approve-{{ $application->id }}"
                class="w-full max-w-md rounded-xl bg-surface-container-high p-6 backdrop:bg-scrim/50">
                <div class="flex flex-col gap-4">
                    <h2 class="text-xl font-medium text-on-surface">Setujui Pengajuan?</h2>
                    <p class="text-sm text-on-surface-variant">
                        Toko <span class="font-medium text-on-surface">{{ $application->nama_toko }}</span>
                        akan dibuat untuk <span class="font-medium text-on-surface">{{ $application->user?->name }}</span>
                        dan akunnya mendapat role <span class="font-mono text-xs">seller</span>.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                            onclick="document.getElementById('dlg-approve-{{ $application->id }}').close()"
                            class="h-10 px-6 rounded-full text-primary text-sm font-medium hover:bg-primary/8 transition-colors">
                            Batal
                        </button>
                        <form method="POST"
                            action="{{ route('admin.seller-applications.approve', $application) }}">
                            @csrf
                            <button type="submit"
                                class="h-10 px-6 rounded-full bg-primary text-on-primary text-sm font-medium
                                       hover:shadow-elev-1 transition-shadow">
                                Setujui
                            </button>
                        </form>
                    </div>
                </div>
            </dialog>

            {{-- Dialog Tolak (catatan wajib) --}}
            <dialog id="dlg-reject-{{ $application->id }}"
                class="w-full max-w-md rounded-xl bg-surface-container-high p-6 backdrop:bg-scrim/50">
                <form method="POST"
                    action="{{ route('admin.seller-applications.reject', $application) }}"
                    class="flex flex-col gap-4">
                    @csrf
                    <h2 class="text-xl font-medium text-on-surface">Tolak Pengajuan</h2>
                    <p class="text-sm text-on-surface-variant">
                        Pengajuan toko <span class="font-medium text-on-surface">{{ $application->nama_toko }}</span>
                        dari {{ $application->user?->name }} akan ditolak.
                    </p>

                    <div class="flex flex-col gap-1">
                        <label for="catatan-{{ $application->id }}"
                            class="text-sm font-medium text-on-surface-variant">
                            Catatan admin (wajib)
                        </label>
                        <textarea id="catatan-{{ $application->id }}" name="catatan_admin" rows="3" required
                            maxlength="2000"
                            placeholder="Alasan penolakan..."
                            class="p-3 rounded-t-lg rounded-b-sm bg-surface-container-highest border-b-2
                                   border-outline text-on-surface placeholder:text-on-surface-variant/60
                                   focus:border-error focus:outline-none transition-colors resize-y"></textarea>
                        @error('catatan_admin')
                            <p class="text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button"
                            onclick="document.getElementById('dlg-reject-{{ $application->id }}').close()"
                            class="h-10 px-6 rounded-full text-primary text-sm font-medium hover:bg-primary/8 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="h-10 px-6 rounded-full bg-error text-on-error text-sm font-medium
                                   hover:shadow-elev-1 transition-shadow">
                            Tolak
                        </button>
                    </div>
                </form>
            </dialog>

        @endif
    @endforeach

@endsection
