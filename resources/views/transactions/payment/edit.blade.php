@extends('layouts.app', ['page' => 'Edit Payment', 'pageSlug' => 'payments', 'section' => 'transactions'])

@section('content')
    <div class="container-fluid mt--7">
        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h3 class="mb-0">Edit Payment</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('transactions.type', ['type' => 'payment']) }}" class="btn btn-sm btn-primary">Back to List</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('transactions.update', $transaction) }}" autocomplete="off" id="the_form">
                            @csrf
                            @method('put')
                            <input type="hidden" name="type" value="{{ $transaction->type }}">
                            <input type="hidden" name="user_id" value="{{ $transaction->user_id }}">
                            <h6 class="heading-small text-muted mb-4">Payment Information</h6>
                            <div class="pl-lg-4">
                                <div class="form-group{{ $errors->has('title') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-title">Title</label>
                                    <input type="text" name="title" id="input-title" class="form-control form-control-alternative{{ $errors->has('title') ? ' is-invalid' : '' }}" placeholder="Title" value="{{ old('title', $transaction->title) }}" required autofocus>
                                    @include('alerts.feedback', ['field' => 'title'])
                                </div>

                                <div class="form-group{{ $errors->has('provider_id') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-provider">Provider</label>
                                    <select name="provider_id" id="input-provider" class="form-select2 form-control-alternative{{ $errors->has('provider_id') ? ' is-invalid' : '' }}" required>
                                        @foreach ($providers as $provider)
                                            @if($provider['id'] == old('provider') or $provider['id'] ==  $transaction->provider_id)
                                                <option value="{{$provider['id']}}" selected>{{$provider['name']}}</option>
                                            @else
                                                <option value="{{$provider['id']}}">{{$provider['name']}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @include('alerts.feedback', ['field' => 'provider_id'])
                                </div>

                                <div class="form-group{{ $errors->has('payment_method_id') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-method">Payment Method</label>
                                    <select name="payment_method_id" id="input-method" class="form-select form-control-alternative{{ $errors->has('payment_method_id') ? ' is-invalid' : '' }}" required>
                                        @foreach ($payment_methods as $payment_method)
                                            @if($payment_method['id'] == old('payment_method_id') or $payment_method['id'] == $transaction->payment_method_id)
                                                <option value="{{$payment_method['id']}}" selected>{{$payment_method['name']}}</option>
                                            @else
                                                <option value="{{$payment_method['id']}}">{{$payment_method['name']}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @include('alerts.feedback', ['field' => 'payment_method_id'])
                                </div>

                                <div class="form-group{{ $errors->has('amount') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-amount">Amount</label>
                                    <input type="number" step=".01" name="amount" id="input-amount" class="form-control form-control-alternative" placeholder="Amount" value="{{ old('amount', $transaction->amount) }}" required>
                                    @include('alerts.feedback', ['field' => 'amount'])
                                </div>

                                <div class="form-group{{ $errors->has('reference') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-reference">Reference</label>
                                    <input type="text" name="reference" id="input-reference" class="form-control form-control-alternative{{ $errors->has('reference') ? ' is-invalid' : '' }}" placeholder="Reference" value="{{ old('reference', $transaction->reference) }}">
                                    @include('alerts.feedback', ['field' => 'reference'])
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-success mt-4" data-toggle="tooltip" onclick="return clicked()">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        function clicked() {
            var title = document.getElementById("input-title").value;
            var provider = document.getElementById("input-provider").selectedOptions[0].text;
            var payment_method = document.getElementById("input-method").selectedOptions[0].text;
            var amount = document.getElementById("input-amount").value;
            var reference = document.getElementById("input-reference").value;
            if (confirm('Do you want to edit this expense?\nPlease check the details\n\nTitle: {{ $transaction->title }} -> ' + title + '\nProvider:@foreach ($providers as $provider) @if($provider['id'] == old('provider') or $provider['id'] ==  $transaction->provider_id){{$provider['name']}}@endif @endforeach-> ' + provider + '\nPayment Method:@foreach ($payment_methods as $payment_method) @if($payment_method['id'] == old('payment_method_id') or $payment_method['id'] == $transaction->payment_method_id){{$payment_method['name']}}@endif @endforeach-> ' + payment_method + '\nAmount: {{ $transaction->amount }} -> ' + amount + '\nReference: {{ $transaction->reference }} -> ' + reference)) {
                document.getElementById("the_form").submit();
            } else {
                return false;
            }
        }
    </script>
@endsection

@push('js')
    <script>
        new SlimSelect({
            select: '.form-select'
        })
        new SlimSelect({
            select: '.form-select2'
        })
    </script>
@endpush('js')