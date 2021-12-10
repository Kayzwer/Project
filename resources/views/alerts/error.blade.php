@if (session($key ?? 'error'))
    <div class="alert alert-danger" role="alert" style="font-weight: bold;">
        {!! session($key ?? 'error') !!}
    </div>
@endif
