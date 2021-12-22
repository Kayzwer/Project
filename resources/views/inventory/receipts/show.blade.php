@extends('layouts.app', ['page' => 'Manage Receipt', 'pageSlug' => 'receipts', 'section' => 'inventory'])


@section('content')
    @include('alerts.success')
    @include('alerts.error')
    <div class="row">
        <div class="col-md-12">
            <div class="card ">
                <div class="card-header">
                    <div class="row">
                        <div class="col-8">
                            <h4 class="card-title">Receipt Summary</h4>
                        </div>
                        @if (!$receipt->finalized_at)
                            <div class="col-4 text-right">
                                @if ($receipt->products->count() === 0)
                                    <form action="{{ route('receipts.destroy', $receipt) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            Delete Receipt
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-primary" onclick="confirm('ATTENTION: At the end of this receipt you will not be able to load more products in it.\n\nPlease check the details @foreach ($receipt->products as $received_product) \nCategory: {{ $received_product->product->category->name }}\nProduct: {{ $received_product->product->name }}\nStock: {{ $received_product->stock }}\nDefective stock: {{ $received_product->stock_defective }}\n @endforeach') ? window.location.replace('{{ route('receipts.finalize', $receipt) }}') : ''">
                                        Finalize Receipt
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Title</th>
                            <th>User</th>
                            <th>Provider</th>
                            <th>products</th>
                            <th>Stock</th>
                            <th>Defective Stock</th>
                            <th>Status</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $receipt->id }}</td>
                                <td>{{ date('d-m-y', strtotime($receipt->created_at)) }}</td>
                                <td style="max-width:150px;">{{ $receipt->title }}</td>
                                <td>{{ $receipt->user->name }}</td>
                                <td>
                                    @if($receipt->provider_id)
                                        <a href="{{ route('providers.show', $receipt->provider) }}">{{ $receipt->provider->name }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $receipt->products->count() }}</td>
                                <td>{{ $receipt->products->sum('stock') }}</td>
                                <td>{{ $receipt->products->sum('stock_defective') }}</td>
                                <td>{!! $receipt->finalized_at ? '<span class="text-success">Finalized</span>' : '<span class="text-danger">TO FINALIZE</span>' !!}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-8">
                            <h4 class="card-title">Number of products: {{ $receipt->products->count() }}</h4>
                        </div>
                        @if (!$receipt->finalized_at)
                            <div class="col-4 text-right">
                                <a href="{{ route('receipts.product.add', ['receipt' => $receipt]) }}" class="btn btn-sm btn-primary">Add</a>
                            </div>
                        @else
                            <div class="col-4 text-right">
                                <a href="{{ route('transactions.create', 'payment') }}" class="btn btn-sm btn-primary">To payment</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <th>Category</th>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Defective Stock</th>
                            <th>Total Stock</th>
                            <th>Price C/U</th>
                            <th>Total</th>
                        </thead>
                        <tbody>
                            @foreach ($receipt->products as $received_product)
                                <tr>
                                    <td><a href="{{ route('categories.show', $received_product->product->category) }}">{{ $received_product->product->category->name }}</a></td>
                                    <td><a href="{{ route('products.show', $received_product->product) }}">{{ $received_product->product->name }}</a></td>
                                    <td>{{ $received_product->stock }}</td>
                                    <td>{{ $received_product->stock_defective }}</td>
                                    <td>{{ $received_product->stock + $received_product->stock_defective }}</td>
                                    <td>{{ $received_product->product->price }}</td>
                                    <td><span class="text-danger">{{ format_money($received_product->product->price * ($received_product->stock + $received_product->stock_defective)) }}</span></td>
                                    <td class="td-actions text-right">
                                        @if(!$receipt->finalized_at)
                                            <a href="{{ route('receipts.product.edit', ['receipt' => $receipt, 'receivedproduct' => $received_product]) }}" class="btn btn-link" data-toggle="tooltip" data-placement="bottom" title="Edit Pedido">
                                                <i class="tim-icons icon-pencil"></i>
                                            </a>
                                            <form action="{{ route('receipts.product.destroy', ['receipt' => $receipt, 'receivedproduct' => $received_product]) }}" method="post" class="d-inline">
                                                @csrf
                                                @method('delete')
                                                <button type="button" class="btn btn-link" data-toggle="tooltip" data-placement="bottom" title="Delete Pedido" onclick="confirm('Are you sure you want to remove this product?\n\nPlease check the details\nProduct: {{ $received_product->product->name }}\nTotal Stock: {{ $received_product->stock + $received_product->stock_defective }}\nPrice: {{ $received_product->product->price }}') ? this.parentElement.submit() : ''">
                                                    <i class="tim-icons icon-simple-remove"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                                <tr>
                                    @php
                                        $total = 0;
                                        foreach ($receipt->products as $received_product)
                                            $total += $received_product->product->price * ($received_product->stock + $received_product->stock_defective);
                                    @endphp
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>Total:</td>
                                    <td><span class="text-danger">{{ format_money($total) }}</span></td> 
                                </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets') }}/js/sweetalerts2.js"></script>
@endpush