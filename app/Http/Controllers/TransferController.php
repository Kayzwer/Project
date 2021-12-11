<?php

namespace App\Http\Controllers;

use App\Transfer;
use App\Transaction;
use App\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Transfer $transfer)
    {
        return view('transfers.index', [
            'transfers' => Transfer::latest()->paginate(25)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('transfers.create', [
            'methods' => PaymentMethod::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Transfer $transfer, Transaction $transaction)
    {
        $transfer = $transfer->create($request->all());

        $transaction->create([
            "type" => "expense",
            "title" => "TransferID: ".$transfer->id,
            "transfer_id" => $transfer->id,
            "payment_method_id" => $transfer->sender_method_id,
            "amount" => ((float) abs($transfer->sended_amount) * (-1)),
            "user_id" => Auth::id(),
            "reference" => $transfer->reference
        ]);

        $transaction->create([
            "type" => "income",
            "title" => "TransferID: ".$transfer->id,
            "transfer_id" => $transfer->id,
            "payment_method_id" => $transfer->receiver_method_id,
            "amount" => abs($transfer->received_amount),
            "user_id" => Auth::id(),
            "reference" => $transfer->reference
        ]);

        return redirect()
            ->route('transfer.index')
            ->withStatus('Transaction registered successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transfer $transfer)
    {
        $transfer->delete();

        return back()
            ->withStatus('Transfer removed successfully.');
    }

    public function export(Request $request)
    {
        $fileName = 'transfers.csv';
        $transfers = DB::table('transfers')
        ->select('transfers.title','transfers.sended_amount','transfers.received_amount','transfers.created_at')
        ->get();
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        $columns = array('title', 'sended_amount', 'received_amount', 'created_at');
        $callback = function() use($transfers, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($transfers as $transfer) {
                $row['title'] = $transfer->title;
                $row['sended_amount'] = $transfer->sended_amount;
                $row['received_amount'] = $transfer->received_amount;
                $row['created_at'] = $transfer->created_at;
                fputcsv($file, array($row['title'], $row['sended_amount'], $row['received_amount'], $row['created_at']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
