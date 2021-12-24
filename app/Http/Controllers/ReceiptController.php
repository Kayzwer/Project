<?php

namespace App\Http\Controllers;

use App\Receipt;
use App\Provider;
use App\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\ReceivedProduct;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Receipt  $model
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $receipts = Receipt::paginate(25);

        return view('inventory.receipts.index', compact('receipts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $providers = Provider::all();

        return view('inventory.receipts.create', compact('providers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Receipt $receipt)
    {
        $receipt = $receipt->create($request->all());

        return redirect()
            ->route('receipts.show', $receipt)
            ->withStatus('Receipt registered successfully, you can start adding the products belonging to it.');
    }

    /**
     * Display the specified resource.
     *
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function show(Receipt $receipt)
    {
        return view('inventory.receipts.show', compact('receipt'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(Receipt $receipt)
    {
        $receipt->delete();

        return redirect()
            ->route('receipts.index')
            ->withStatus('Receipt removed successfully.');
    }

    /**
     * Finalize the Receipt for stop adding products.
     *
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function finalize(Receipt $receipt)
    {
        $receipt->finalized_at = Carbon::now()->toDateTimeString();
        $receipt->save();

        foreach($receipt->products as $receivedproduct) {
            $receivedproduct->product->stock += $receivedproduct->stock;
            $receivedproduct->product->stock_defective += $receivedproduct->stock_defective;
            $receivedproduct->product->save();
        }

        return back()->withStatus('Receipt completed successfully.');
    }

    /**
     * Add product on Receipt.
     *
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function addproduct(Receipt $receipt)
    {
        $products = Product::all();

        return view('inventory.receipts.addproduct', compact('receipt', 'products'));
    }

    /**
     * Add product on Receipt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function storeproduct(Request $request, Receipt $receipt, ReceivedProduct $receivedproduct)
    {
        $receivedproduct->create($request->all());

        return redirect()
            ->route('receipts.show', $receipt)
            ->withStatus('Product added successfully.');
    }

    /**
     * Editor product on Receipt.
     *
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function editproduct(Receipt $receipt, ReceivedProduct $receivedproduct)
    {
        $products = Product::all();

        return view('inventory.receipts.editproduct', compact('receipt', 'receivedproduct', 'products'));
    }

    /**
     * Update product on Receipt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function updateproduct(Request $request, Receipt $receipt, ReceivedProduct $receivedproduct)
    {
        $receivedproduct->update($request->all());

        return redirect()
            ->route('receipts.show', $receipt)
            ->withStatus('Product updated successfully.');
    }

    /**
     * Add product on Receipt.
     *
     * @param  Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function destroyproduct(Receipt $receipt, ReceivedProduct $receivedproduct)
    {
        $receivedproduct->delete();

        return redirect()
            ->route('receipts.show', $receipt)
            ->withStatus('Product removed successfully.');
    }

    public function export(Request $request)
    {
        $fileName = 'receipts.csv';
        $receipts = Receipt::all();
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('title', 'provider', 'number_of_products', 'stock', 'defective_stock', 'created_at');
        $callback = function() use($receipts, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($receipts as $receipt) {
                $row['title'] = $receipt->title;
                $row['provider'] = $receipt->provider->name;
                $row['number_of_products'] = $receipt->products->count();
                $row['stock'] = $receipt->products->sum('stock');
                $row['defective_stock'] = $receipt->products->sum('stock_defective');
                $row['created_at'] = $receipt->created_at;
                fputcsv($file, array($row['title'], $row['provider'], $row['number_of_products'], $row['stock'], $row['defective_stock'], $row['created_at']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
