@php
    $toneClasses = [
        'neutral' => 'bg-surface-container-highest text-on-surface-variant',
        'success' => 'bg-tertiary-container text-on-tertiary-container',
        'warning' => 'bg-primary-container text-on-primary-container',
        'error' => 'bg-error-container text-on-error-container',
        'info' => 'bg-secondary-container text-on-secondary-container',
    ];
@endphp
<span class="inline-flex items-center h-6 px-2 rounded-full text-xs font-medium {{ $toneClasses[$tone ?? 'neutral'] ?? $toneClasses['neutral'] }}">{{ $text ?? '' }}</span>
