@if (session($key ?? 'status'))
    <div class="alert alert-success" role="alert" style="font-weight: bold;">
        {!! session($key ?? 'status') !!}
    </div>
@endif
