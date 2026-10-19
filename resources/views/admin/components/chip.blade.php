{{--
  Chip M3 (filter/assist). Props: active (bool), href (opsional -> link <a>)
  Contoh: <x-admin.chip :active="true">Pending</x-admin.chip>
--}}
@props([
    'active' => false,
    'href' => null,
])

@php
    $base = 'inline-flex items-center gap-1 h-8 px-4 rounded-full text-sm border transition-colors duration-150';
    $classes = $active
        ? 'bg-secondary-container text-on-secondary-container border-transparent'
        : 'bg-surface text-on-surface-variant border-outline-variant hover:bg-surface-container';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base . ' ' . $classes]) }}>
        {{ $slot ?? '' }}
    </a>
@else
    <button type="submit" {{ $attributes->merge(['class' => $base . ' ' . $classes]) }}>
        {{ $slot ?? '' }}
    </button>
@endif
