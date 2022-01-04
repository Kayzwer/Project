<?php

namespace App\Http\Controllers;

use App\Sale;
use App\Client;
use App\Provider;
use Carbon\Carbon;
use App\SoldProduct;
use App\Transaction;
use App\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transactionname = [
            'income' => 'Income',
            'payment' => 'Payment',
            'expense' => 'Expense',
            'transfer' => 'Transfer'
        ];

        $transactions = Transaction::latest()->paginate(25);

        return view('transactions.index', compact('transactions', 'transactionname'));
        // Return transactions.index page with splitted transactions in array
    }

    public function stats()
    {
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
        
        $salesperiods = [];
        $transactionsperiods = [];

        $salesperiods['Day'] = Sale::whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();
        $transactionsperiods['Day'] = Transaction::whereBetween('created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()])->get();

        $salesperiods['Yesterday'] = Sale::whereBetween('created_at', [Carbon::now()->subDay(1)->startOfDay(), Carbon::now()->subDay(1)->endOfDay()])->get();
        $transactionsperiods['Yesterday'] = Transaction::whereBetween('created_at', [Carbon::now()->subDay(1)->startOfDay(), Carbon::now()->subDay(1)->endOfDay()])->get();

        $salesperiods['Week'] = Sale::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
        $transactionsperiods['Week'] = Transaction::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();

        $salesperiods['Month'] = Sale::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();
        $transactionsperiods['Month'] = Transaction::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->get();

        $salesperiods['Trimester'] = Sale::whereBetween('created_at', [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()])->get();
        $transactionsperiods['Trimester'] = Transaction::whereBetween('created_at', [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()])->get();

        $salesperiods['Year'] = Sale::whereYear('created_at', Carbon::now()->year)->get();
        $transactionsperiods['Year'] = Transaction::whereYear('created_at', Carbon::now()->year)->get();

        return view('transactions.stats', [
            'clients'               => Client::where('balance', '!=', '0.00')->get(),
            'salesperiods'          => $salesperiods,
            'transactionsperiods'   => $transactionsperiods,
            'date'                  => Carbon::now(),
            'methods'               => PaymentMethod::all()
        ]);
        // Calculate the statistics the needed and return transactions.stats page with these statistic
    }

    public function type($type)
    {
        switch ($type) {
            case 'expense':
                return view('transactions.expense.index', ['transactions' => Transaction::where('type', 'expense')->latest()->paginate(25)]);

            case 'payment':
                return view('transactions.payment.index', ['transactions' => Transaction::where('type', 'payment')->latest()->paginate(25)]);

            case 'income':
                return view('transactions.income.index', ['transactions' => Transaction::where('type', 'income')->latest()->paginate(25)]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($type)
    {
        switch ($type) {
            case 'expense':
                return view('transactions.expense.create', [
                    'payment_methods' => PaymentMethod::all(),
                ]);

            case 'payment':
                return view('transactions.payment.create', [
                    'payment_methods' => PaymentMethod::all(),
                    'providers' => Provider::all(),
                ]);

            case 'income':
                return view('transactions.income.create', [
                    'payment_methods' => PaymentMethod::all(),
                ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Transaction $transaction)
    {
        if ($request->get('client_id')) {
            switch ($request->get('type')) {
                case 'income':
                    $request->merge(['title' => 'Payment Received from Customer ID: ' . $request->get('client_id')]);
                    break;

                case 'expense':
                    $request->merge(['title' => 'Customer ID Return Payment: ' . $request->get('client_id')]);

                    if ($request->get('amount') > 0) {
                        $request->merge(['amount' => (float) $request->get('amount') * (-1)]);
                    }
                    break;
            }

            $transaction->create($request->all());
            $client = Client::find($request->get('client_id'));
            $client->balance += $request->get('amount');
            $client->save();

            return redirect()
                ->route('clients.show', $request->get('client_id'))
                ->withStatus('Transaction registered successfully.');
        }

        switch ($request->get('type')) {
            case 'expense':
                if ($request->get('amount') > 0) {
                    $request->merge(['amount' => ((float) $request->get('amount') * (-1))]);
                }

                $transaction->create($request->all());

                return redirect()
                    ->route('transactions.type', ['type' => 'expense'])
                    ->withStatus('Expense recorded successfully.');

            case 'payment':
                if ($request->get('amount') > 0) {
                    $request->merge(['amount' => ((float) $request->get('amount') * (-1))]);
                }

                $transaction->create($request->all());

                return redirect()
                    ->route('transactions.type', ['type' => 'payment'])
                    ->withStatus('Payment registered successfully.');

            case 'income':
                $transaction->create($request->all());

                return redirect()
                    ->route('transactions.type', ['type' => 'income'])
                    ->withStatus('Login registered successfully.');

            default:
                return redirect()
                    ->route('transactions.index')
                    ->withStatus('Transaction registered successfully.');
        }
    }

    /** 
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        switch ($transaction->type) {
            case 'expense':
                return view('transactions.expense.edit', [
                    'transaction' => $transaction,
                    'payment_methods' => PaymentMethod::all()
                ]);

            case 'payment':
                return view('transactions.payment.edit', [
                    'transaction' => $transaction,
                    'payment_methods' => PaymentMethod::all(),
                    'providers' => Provider::all()
                ]);

            case 'income':
                return view('transactions.income.edit', [
                    'transaction' => $transaction,
                    'payment_methods' => PaymentMethod::all(),
                ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        $transaction->update($request->all());

        switch ($request->get('type')) {
            case 'expense':
                if ($request->get('amount') > 0) {
                    $request->merge(['amount' => ((float) $request->get('amount') * (-1))]);
                }
                return redirect()
                    ->route('transactions.type', ['type' => 'expense'])
                    ->withStatus('Expense updated sucessfully.');

            case 'payment':
                if ($request->get('amount') > 0) {
                    $request->merge(['amount' => ((float) $request->get('amount') * (-1))]);
                }

                return redirect()
                    ->route('transactions.type', ['type' => 'payment'])
                    ->withStatus('Payment updated successfully.');

            case 'income':
                return redirect()
                    ->route('transactions.type', ['type' => 'income'])
                    ->withStatus('Income updated successfully.');

            default:
                return redirect()
                    ->route('transactions.index')
                    ->withStatus('Transaction updated successfully.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        //if ($transaction->sale)
        //{
        //    return back()->withStatus('You cannot remove a transaction from a completed sale. You can delete the sale and its entire record.');
        //}

        if ($transaction->transfer) {
            return back()->withStatus('You cannot remove a transaction from a transfer. You must delete the transfer to delete its records.');
        }

        $type = $transaction->type;
        $transaction->delete();

        switch ($type) {
            case 'expense':
                return back()->withStatus('Expenditure removed successfully.');

            case 'payment':
                return back()->withStatus('Payment removed successfully.');

            case 'income':
                return back()->withStatus('Income removed successfully.');

            default:
                return back()->withStatus('Transaction removed successfully.');
        }
    }

    public function export(Request $request)
    {
        $fileName = 'transactions.csv';
        $transactions = Transaction::all();
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('created_at', 'type', 'title', 'amount');
        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $transaction) {
                $row['created_at'] = $transaction->created_at;
                $row['type'] = $transaction->type;
                $row['title'] = $transaction->title;
                $row['amount'] = $transaction->amount;

                fputcsv($file, array($row['created_at'], $row['type'], $row['title'], $row['amount']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportExpenses(Request $request)
    {
        $fileName = 'expenses.csv';
        $expenses = DB::table('transactions')
        ->where('transactions.type','expense')
        ->select('transactions.title','transactions.payment_method_id',DB::raw('ABS(transactions.amount) AS amount'),'transactions.created_at')
        ->get();
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('title', 'payment_method_id', 'amount', 'created_at');
        $callback = function() use($expenses, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($expenses as $expense) {
                $row['title'] = $expense->title;
                $row['payment_method_id'] = $expense->payment_method_id;
                $row['amount'] = $expense->amount;
                $row['created_at'] = $expense->created_at;

                fputcsv($file, array($row['title'], $row['payment_method_id'], $row['amount'], $row['created_at']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportIncomes(Request $request)
    {
        $fileName = 'incomes.csv';
        $incomes = DB::table('transactions')
        ->Join('payment_methods','transactions.payment_method_id','=','payment_methods.id')
        ->where('type','income')
        ->select('transactions.title','payment_methods.name','transactions.amount','transactions.created_at')
        ->get();
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        $columns = array('title', 'payment_method', 'amount', 'created_at');
        $callback = function() use($incomes, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($incomes as $income) {
                $row['title'] = $income->title;
                $row['name'] = $income->name;
                $row['amount'] = $income->amount;
                $row['created_at'] = $income->created_at;
                fputcsv($file, array($row['title'], $row['name'], $row['amount'], $row['created_at']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportPayments(Request $request)
    {
        $fileName = 'payments.csv';
        $payments = DB::table('transactions')
        ->Join('providers','transactions.provider_id','=','providers.id')
        ->Join('payment_methods','transactions.payment_method_id','=','payment_methods.id')
        ->where('transactions.type','payment')
        ->select('transactions.title','providers.name AS provider',DB::raw('ABS(transactions.amount) AS amount'),'payment_methods.name','transactions.created_at')
        ->get();
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        $columns = array('title', 'provider', 'amount', 'payment_method', 'created_at');
        $callback = function() use($payments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($payments as $payment) {
                $row['title'] = $payment->title;
                $row['provider'] = $payment->provider;
                $row['amount'] = $payment->amount;
                $row['payment_method'] = $payment->name;
                $row['created_at'] = $payment->created_at;
                fputcsv($file, array($row['title'], $row['provider'], $row['amount'], $row['payment_method'], $row['created_at']));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
