{{--
  Kartu M3. Props: padding (md default), variant: low|default|high
  Contoh: <x-admin.card>Judul kartu</x-admin.card>
--}}
@props([
    'padding' => 'p-4',
    'variant' => 'default',
])

@php
    $surface = [
        'low' => 'bg-surface-container-low',
        'default' => 'bg-surface-container',
        'high' => 'bg-surface-container-high',
    ][$variant] ?? 'bg-surface-container';

    $classes = $surface . ' rounded-lg shadow-elev-1 ' . $padding;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
