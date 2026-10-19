{{--
  Badge status M3. Props: tone = neutral|success|warning|error|info
  Contoh: <x-admin.badge tone="success">approved</x-admin.badge>
--}}
@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'bg-surface-container-highest text-on-surface-variant',
        'success' => 'bg-tertiary-container text-on-tertiary-container',
        'warning' => 'bg-primary-container text-on-primary-container',
        'error' => 'bg-error-container text-on-error-container',
        'info' => 'bg-secondary-container text-on-secondary-container',
    ];
    $classes = 'inline-flex items-center h-6 px-2 rounded-full text-xs font-medium '
        . ($tones[$tone] ?? $tones['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot ?? '' }}
</span>
