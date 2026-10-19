{{--
  Snackbar M3 (notifikasi bawah). Props: id, tone = success|error
  Contoh: <x-admin.snackbar id="snack" tone="success">Berhasil.</x-admin.snackbar>
--}}
@props([
    'id',
    'tone' => 'success',
])

@php
    $classes = $tone === 'error'
        ? 'bg-error-container text-on-error-container'
        : 'bg-inverse-surface text-inverse-on-surface';
@endphp

<div id="{{ $id }}" hidden
    {{ $attributes->merge(['class' => 'fixed bottom-4 left-1/2 -translate-x-1/2 z-50 min-w-80 max-w-md '
        . 'rounded-lg shadow-elev-3 px-4 py-3 text-sm ' . $classes]) }}>
    {{ $slot }}
</div>
