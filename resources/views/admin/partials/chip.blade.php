@php
    $chipClasses = $active ?? false
        ? 'bg-secondary-container text-on-secondary-container border-transparent'
        : 'bg-surface text-on-surface-variant border-outline-variant hover:bg-surface-container';
@endphp
@if (! empty($href))
    <a href="{{ $href }}" class="inline-flex items-center gap-1 h-8 px-4 rounded-full text-sm border transition-colors duration-150 {{ $chipClasses }}">{{ $text ?? '' }}</a>
@else
    <span class="inline-flex items-center gap-1 h-8 px-4 rounded-full text-sm border transition-colors duration-150 {{ $chipClasses }}">{{ $text ?? '' }}</span>
@endif
