@if ($errors->has($field))
    <span class="invalid-feedback" role="alert" style="font-weight: bold;">{{ $errors->first($field) }}</span>
@endif
