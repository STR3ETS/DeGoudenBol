@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'help' => null,
    'placeholder' => 'Selecteer…',
])
@php
    $id = $attributes->get('id', 'veld-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $error = $errors->first($name);
    $selected = old($name, $value);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="label">
        {{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif
    </label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        class="input input--select"
        @if ($required) required aria-required="true" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-fout" @elseif ($help) aria-describedby="{{ $id }}-hulp" @endif
        {{ $attributes->except(['class', 'id']) }}
    >
        @if ($placeholder)
            <option value="" @selected($selected === null || $selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($error)
        <p id="{{ $id }}-fout" class="field-error">{{ $error }}</p>
    @elseif ($help)
        <p id="{{ $id }}-hulp" class="field-help">{{ $help }}</p>
    @endif
</div>
