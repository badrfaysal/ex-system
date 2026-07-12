<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialLogController extends Controller
{
    public function index(Request $request)
    {
        $receipts = DB::table('client_receipts')
            ->leftJoin('clients', 'client_receipts.client_id', '=', 'clients.id')
            ->select('client_receipts.id', 'receipt_date as transaction_date', 'client_receipts.created_at', DB::raw("'receipt' as type"), 'receipt_number as ref', 'amount', 'wallet_id', 'created_by as user_id', 'clients.company_name as detail');

        $revenues = DB::table('revenues')
            ->select('id', 'revenue_date as transaction_date', 'created_at', DB::raw("'revenue' as type"), 'revenue_number as ref', 'amount', 'wallet_id', 'created_by as user_id', 'category as detail');

        $expenses = DB::table('expenses')
            ->select('id', 'expense_date as transaction_date', 'created_at', DB::raw("'expense' as type"), 'expense_number as ref', DB::raw('amount * -1 as amount'), 'wallet_id', 'created_by as user_id', 'category as detail');

        $payments = DB::table('vendor_payments')
            ->leftJoin('vendors', 'vendor_payments.vendor_id', '=', 'vendors.id')
            ->select('vendor_payments.id', 'payment_date as transaction_date', 'vendor_payments.created_at', DB::raw("'vendor_payment' as type"), 'payment_number as ref', DB::raw('vendor_payments.amount * -1 as amount'), 'wallet_id', 'created_by as user_id', 'vendors.name_ar as detail');

        $transfersOut = DB::table('wallet_transfers')
            ->leftJoin('wallets', 'wallet_transfers.to_wallet_id', '=', 'wallets.id')
            ->select('wallet_transfers.id', 'transfer_date as transaction_date', 'wallet_transfers.created_at', DB::raw("'transfer_out' as type"), 'transfer_number as ref', DB::raw('wallet_transfers.amount * -1 as amount'), 'from_wallet_id as wallet_id', 'created_by as user_id', 'wallets.name as detail');

        $transfersIn = DB::table('wallet_transfers')
            ->leftJoin('wallets', 'wallet_transfers.from_wallet_id', '=', 'wallets.id')
            ->select('wallet_transfers.id', 'transfer_date as transaction_date', 'wallet_transfers.created_at', DB::raw("'transfer_in' as type"), 'transfer_number as ref', 'wallet_transfers.amount as amount', 'to_wallet_id as wallet_id', 'created_by as user_id', 'wallets.name as detail');

        $query = $receipts->unionAll($revenues)
                          ->unionAll($expenses)
                          ->unionAll($payments)
                          ->unionAll($transfersOut)
                          ->unionAll($transfersIn);

        $logsQuery = DB::query()->fromSub($query, 'logs');

        if ($request->filled('date_from')) {
            $logsQuery->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $logsQuery->where('transaction_date', '<=', $request->date_to);
        }

        $totalsQuery = clone $logsQuery;
        $totals = $totalsQuery->selectRaw('
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_in,
            SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as total_out
        ')->first();

        $sort = $request->get('sort', 'date_desc');
        switch ($sort) {
            case 'date_asc':
                $logsQuery->orderBy('transaction_date', 'asc')->orderBy('created_at', 'asc');
                break;
            case 'amount_desc':
                $logsQuery->orderBy('amount', 'desc');
                break;
            case 'amount_asc':
                $logsQuery->orderBy('amount', 'asc');
                break;
            case 'date_desc':
            default:
                $logsQuery->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc');
                break;
        }

        $totalIn = $totals->total_in ?? 0;
        $totalOut = abs($totals->total_out ?? 0);

        $logs = $logsQuery->paginate(20)->withQueryString();

        $walletIds = $logs->pluck('wallet_id')->unique();
        $userIds = $logs->pluck('user_id')->unique();

        $walletsMap = Wallet::whereIn('id', $walletIds)->pluck('name', 'id');
        $usersMap = User::whereIn('id', $userIds)->pluck('name', 'id');

        $logs->getCollection()->transform(function ($log) use ($walletsMap, $usersMap) {
            $log->wallet_name = $walletsMap[$log->wallet_id] ?? '—';
            $log->user_name = $usersMap[$log->user_id] ?? '—';
            return $log;
        });

        return view('financial_logs.index', compact('logs', 'totalIn', 'totalOut', 'sort'));
    }
}
