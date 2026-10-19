{{--
  Text field outline M3 (label melayang via placeholder + label statis di atas).
  Contoh: <x-admin.field label="Email" name="email" type="email" />
--}}
@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'autofocus' => false,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    @if ($label)
        <label for="{{ $name }}" class="text-sm font-medium text-on-surface-variant">{{ $label }}</label>
    @endif
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        class="h-14 px-4 rounded-t-lg rounded-b-sm bg-surface-container-highest border-b-2
               border-outline text-on-surface placeholder:text-on-surface-variant/60
               focus:border-primary focus:outline-none transition-colors duration-150">
    {{ \$slot->isNotEmpty() ? \$slot : (\$text ?? '') }}
</div>
