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
        // Divide each page to to contain 25 clients

        return view('clients.index', compact('clients'));
        // Return clients.index html code to user with clients as an array
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('clients.create');
        // Return clients.create html code to user
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
        // Get all request from view component and create new record and store in the client database.
        
        return redirect()->route('clients.index')->withStatus('Client registered successfully.');
        // Redirect user to client list page with message 'Customer registered successfully.'
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
        // Return clients.show html code to user with client as an array
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
        // Return clients.edit html code to user with client as an array
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
        // Get all request from view component and update the client record

        return redirect()
            ->route('clients.index')
            ->withStatus('Client updated successfully.');
        // Redirect user to client list page with message 'Customer updated successfully.'
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
        // Delete the client record

        return redirect()
            ->route('clients.index')
            ->withStatus('Client removed successfully.');
        // Redirect user to client list page with message 'Customer removed successfully.'
    }

    public function addtransaction(Client $client)
    {
        $payment_methods = PaymentMethod::all();
        // Get all payment methods from DB

        return view('clients.transactions.add', compact('client','payment_methods'));
        // Return clients.transactions.add html code to user with client data and payment methods in array
    }

    public function export(Request $request)
    {
        $fileName = 'clients.csv'; // Define .csv file name
        $clients = DB::table('clients')
        ->Join('sales','sales.client_id','=','clients.id')
        ->select('clients.name','clients.created_at',DB::raw('SUM(sales.total_amount) AS total_amount'),DB::raw('COUNT(sales.client_id) AS purchases'))
        ->groupBy('clients.id')
        ->get();
        // Query the needed data from DB
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('name', 'created_at', 'total_amount', 'purchases'); // Define columns in the csv file
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
            // Write the data into the csv and close the writer
        };
        return response()->stream($callback, 200, $headers);
        // Create a new streamed response object to make a download file
    }
}