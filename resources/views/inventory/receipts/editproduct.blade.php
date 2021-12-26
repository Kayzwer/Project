@extends('layouts.app', ['page' => 'Edit Product', 'pageSlug' => 'receipts', 'section' => 'inventory'])

@section('content')
        <div class="row">
            <div class="col-xl-12 order-xl-1">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h3 class="mb-0">Edit Product</h3>
                            </div>
                            <div class="col-4 text-right">
                                <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-sm btn-primary">Back to List</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('receipts.product.update', ['receipt' => $receipt, 'receivedproduct' => $receivedproduct]) }}" autocomplete="off">
                            @csrf
                            @method('put')

                            <div class="pl-lg-4">
                                <input type="hidden" name="receipt_id" value="{{ $receipt->id }}">
                                <div class="form-group{{ $errors->has('product_id') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-product">Product</label>
                                    <select name="product_id" id="input-product" class="form-select form-control-alternative{{ $errors->has('product_id') ? ' is-invalid' : '' }}" required>
                                        @foreach ($products as $product)
                                            @if($product['id'] == old('product_id') or $product['id'] == $receivedproduct->product_id )
                                                <option value="{{$product['id']}}" selected>[{{ $product->category->name }}] {{ $product->name }} - Base price: {{ format_money($product->price) }}</option>
                                            @else
                                                <option value="{{$product['id']}}">[{{ $product->category->name }}] {{ $product->name }} - Base price: {{ format_money($product->price) }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @include('alerts.feedback', ['field' => 'product_id'])
                                </div>

                                <div class="form-group{{ $errors->has('product_id') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-stock">Stock</label>
                                    <input type="number" name="stock" id="input-stock" class="form-control form-control-alternative{{ $errors->has('product_id') ? ' is-invalid' : '' }}" value="{{ old('stock', $receivedproduct->stock) }}" required>
                                    @include('alerts.feedback', ['field' => 'product_id'])
                                </div>

                                <div class="form-group{{ $errors->has('product_id') ? ' has-danger' : '' }}">
                                    <label class="form-control-label" for="input-stock_defective">Defective Stock</label>
                                    <input type="number" name="stock_defective" id="input-stock_defective" class="form-control form-control-alternative{{ $errors->has('product_id') ? ' is-invalid' : '' }}" value="{{ old('stock_defective', $receivedproduct->stock_defective) }}" required>
                                    @include('alerts.feedback', ['field' => 'product_id'])
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-success mt-4" data-toggle="tooltip" onclick="return clicked()">Continue</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            function clicked() {
                var product = document.getElementById("input-product").selectedOptions[0].text;
                var stock = document.getElementById("input-stock").value;
                var defective_stock = document.getElementById("input-stock_defective").value;
                if (confirm('Do you want to edit this product?\nPlease check the details\n\nProduct:@foreach ($products as $product) @if($product['id'] == old('product_id') or $product['id'] == $receivedproduct->product_id )[{{ $product->category->name }}] {{$product->name}} - Base price: {{ format_money($product->price) }}@endif @endforeach-> ' + product + '\nStock: {{$receivedproduct->stock}} -> ' + stock + '\nDefective stock: {{$receivedproduct->stock_defective}} -> ' + defective_stock)) {
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
        });
    </script>
@endpush