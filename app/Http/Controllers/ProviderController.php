<?php

namespace App\Http\Controllers;

use App\Provider;
use App\Http\Requests\ProviderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
    /**
     * Display a listing of the Provs
     *
     * @param  \App\Provider  $model
     * @return \Illuminate\View\View
     */
    public function index(Provider $model)
    {
        $providers = Provider::paginate(25);

        return view('providers.index', compact('providers'));
    }

    /**
     * Show the form for creating a new Prov
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('providers.create');
    }

    /**
     * Store a newly created Provider in storage
     *
     * @param  \App\Http\Requests\ProviderRequest  $request
     * @param  \App\Provider  $model
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ProviderRequest $request, Provider $provider)
    {
        $provider->create($request->all());

        return redirect()
            ->route('providers.index')
            ->withStatus('Provider registered successfully.');
    }

    /**
     * Show the form for editing the specified Provider
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\View\View
     */
    public function edit(Provider $provider)
    {
        return view('providers.edit', compact('provider'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Provider $provider)
    {
        $transactions = $provider->transactions()->latest()->limit(25)->get();

        $receipts = $provider->receipts()->latest()->limit(25)->get();

        return view('providers.show', compact('provider', 'transactions', 'receipts'));
    }

    /**
     * Update the specified Provider in storage
     *
     * @param  \App\Http\Requests\ProviderRequest  $request
     * @param  \App\Provider  $Provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProviderRequest $request, Provider $provider)
    {
        $provider->update($request->all());

        return redirect()
            ->route('providers.index')
            ->withStatus('Provider updated successfully.');
    }

    /**
     * Remove the specified Provider from storage
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Provider $provider)
    {
        $provider->delete();

        return redirect()
            ->route('providers.index')
            ->withStatus('Provider removed successfully.');
    }

    public function export(Request $request)
    {
        $fileName = 'providers.csv';
        $providers = DB::table('providers')
        ->Join('transactions','transactions.provider_id','=','providers.id')
        ->select('providers.name','providers.paymentinfo','providers.created_at',DB::raw('ABS(SUM(transactions.amount)) AS amount'))
        ->groupBy('providers.id')
        ->get();
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('name', 'paymentinfo', 'created_at', 'amount');
        $callback = function() use($providers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($providers as $provider) {
                $row['name'] = $provider->name;
                $row['paymentinfo'] = $provider->paymentinfo;
                $row['created_at'] = $provider->created_at;
                $row['amount'] = $provider->amount;
                fputcsv($file, array($row['name'], $row['paymentinfo'], $row['created_at'], $row['amount']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
