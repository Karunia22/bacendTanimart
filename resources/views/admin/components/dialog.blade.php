{{--
  Dialog M3 (modal). Props: id (wajib), title.
  Konten aksi ditaruh manual di slot; ditutup dengan tombol type=button
  yang memakai data-dialog-close="ID".
  Contoh:
    <x-admin.dialog id="dlg-1" title="Konfirmasi">Isi...</x-admin.dialog>
--}}
@props([
    'id',
    'title' => '',
])

<dialog id="{{ $id }}"
    {{ $attributes->merge(['class' => 'w-full max-w-md rounded-xl bg-surface-container-high p-6 backdrop:bg-scrim/50']) }}>
    <form method="dialog" class="flex flex-col gap-4">
        <h2 class="text-xl font-medium text-on-surface">{{ $title }}</h2>
        <div class="text-sm text-on-surface-variant">{{ $slot }}</div>
        <div class="flex justify-end gap-2 mt-2">
            {{ $actions ?? '' }}
        </div>
    </form>
</dialog>
