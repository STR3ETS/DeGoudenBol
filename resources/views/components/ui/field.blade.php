@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'help' => null,
    'placeholder' => null,
    'autocomplete' => null,
])
@php
    $id = $attributes->get('id', 'veld-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $error = $errors->first($name);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="label">
        {{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        class="input"
        @if ($required) required aria-required="true" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-fout" @elseif ($help) aria-describedby="{{ $id }}-hulp" @endif
        {{ $attributes->except(['class', 'id']) }}
    >
    @if ($error)
        <p id="{{ $id }}-fout" class="field-error">{{ $error }}</p>
    @elseif ($help)
        <p id="{{ $id }}-hulp" class="field-help">{{ $help }}</p>
    @endif
</div>
