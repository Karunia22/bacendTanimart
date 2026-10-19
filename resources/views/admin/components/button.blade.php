{{--
  Tombol M3. Variasi: filled | tonal | outlined | text
  Contoh: <x-admin.button>Teks</x-admin.button>
          <x-admin.button variant="tonal" icon="check">Setujui</x-admin.button>
--}}
@props([
    'variant' => 'filled',
    'type' => 'button',
    'icon' => null,
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 h-10 px-6 rounded-full text-sm font-medium '
        . 'transition-colors duration-150 ease-out disabled:opacity-40 disabled:pointer-events-none md-ripple';

    $variants = [
        'filled' => 'bg-primary text-on-primary hover:shadow-elev-1',
        'tonal' => 'bg-secondary-container text-on-secondary-container hover:shadow-elev-1',
        'outlined' => 'border border-outline text-primary bg-transparent',
        'text' => 'text-primary bg-transparent',
        'error' => 'bg-error text-on-error',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['filled']);
@endphp

<button {{ $attributes->merge(['class' => $classes]) }}
    type="{{ $type }}"
    @if ($disabled) disabled @endif>
    @if ($icon)
        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">{{ $icon }}</span>
    @endif
    {{ $slot ?? '' }}
</button>
