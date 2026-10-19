{{--
  Ikon M3. Contoh: <x-admin.icon>home</x-admin.icon>
--}}
@props([
    'filled' => false,
    'size' => 'text-[20px]',
])

<span {{ $attributes->merge(['class' => 'material-symbols-outlined ' . $size . ($filled ? ' filled' : '')]) }}
    aria-hidden="true">{{ $slot ?? '' }}</span>
