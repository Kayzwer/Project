<?php

namespace App\Http\Controllers;

use App\Sale;
use App\Client;
use App\Transaction;
use App\PaymentMethod;
use Illuminate\Http\Request;
use App\Http\Requests\ClientRequest;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clients = Client::paginate(25);

        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Request\ClientRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ClientRequest $request, Client $client)
    {
        $client->create($request->all());
        
        return redirect()->route('clients.index')->withStatus('Successfully registered customer.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Client $client)
    {
        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Request\ClientRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->all());

        return redirect()
            ->route('clients.index')
            ->withStatus('Successfully modified customer.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()
            ->route('clients.index')
            ->withStatus('Customer successfully removed.');
    }

    public function addtransaction(Client $client)
    {
        $payment_methods = PaymentMethod::all();

        return view('clients.transactions.add', compact('client','payment_methods'));
    }

    public function export(Request $request)
    {
        $fileName = 'clients.csv';
        $clients = DB::table('clients')
        ->Join('sales','sales.client_id','=','clients.id')
        ->select('clients.name','clients.created_at',DB::raw('SUM(sales.total_amount) AS total_amount'),DB::raw('COUNT(sales.client_id) AS purchases'))
        ->groupBy('clients.id')
        ->get();
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('name', 'created_at', 'total_amount', 'purchases');
        $callback = function() use($clients, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($clients as $client) {
                $row['name'] = $client->name;
                $row['created_at'] = $client->created_at;
                $row['total_amount'] = $client->total_amount;
                $row['purchases'] = $client->purchases;
                fputcsv($file, array($row['name'], $row['created_at'], $row['total_amount'], $row['purchases']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}