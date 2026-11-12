@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false, 'max' => null, 'maxlength' => null])
<div {{ $attributes->class(['mb-3']) }}>
    <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="required" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" class="form-control @error($name) is-invalid @enderror" @required($required) @if($max !== null) max="{{ $max }}" @endif @if($maxlength) maxlength="{{ $maxlength }}" @endif @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @error($name)<div id="{{ $name }}-error" class="invalid-feedback">{{ $message }}</div>@enderror
</div>
