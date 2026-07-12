<?php

namespace App\Http\Controllers;

use App\Models\ClientReceipt;
use App\Models\Expense;
use App\Models\Revenue;
use App\Models\User;
use App\Models\VendorPayment;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FinancialLogController extends Controller
{
    /**
     * خريطة نوع السجل المعروض (source_type) إلى الموديل الفعلي — تُستخدم في reverse().
     * transfer_out و transfer_in وجهان لنفس سطر wallet_transfers، فالاتنين بيأشروا لنفس النوع "transfer".
     */
    private const REVERSIBLE_MODELS = [
        'receipt'        => ClientReceipt::class,
        'revenue'        => Revenue::class,
        'expense'        => Expense::class,
        'vendor_payment' => VendorPayment::class,
        'transfer'       => WalletTransfer::class,
    ];

    public function index(Request $request)
    {
        $receipts = DB::table('client_receipts')
            ->leftJoin('clients', 'client_receipts.client_id', '=', 'clients.id')
            ->select('client_receipts.id', 'receipt_date as transaction_date', 'client_receipts.created_at', DB::raw("'receipt' as type"), DB::raw("'receipt' as source_type"), 'receipt_number as ref', 'amount', 'wallet_id', 'created_by as user_id', 'clients.company_name as detail', 'client_receipts.reversed_at', 'client_receipts.reversal_reason');

        $revenues = DB::table('revenues')
            ->select('id', 'revenue_date as transaction_date', 'created_at', DB::raw("'revenue' as type"), DB::raw("'revenue' as source_type"), 'revenue_number as ref', 'amount', 'wallet_id', 'created_by as user_id', 'category as detail', 'reversed_at', 'reversal_reason');

        $expenses = DB::table('expenses')
            ->select('id', 'expense_date as transaction_date', 'created_at', DB::raw("'expense' as type"), DB::raw("'expense' as source_type"), 'expense_number as ref', DB::raw('amount * -1 as amount'), 'wallet_id', 'created_by as user_id', 'category as detail', 'reversed_at', 'reversal_reason');

        $payments = DB::table('vendor_payments')
            ->leftJoin('vendors', 'vendor_payments.vendor_id', '=', 'vendors.id')
            ->select('vendor_payments.id', 'payment_date as transaction_date', 'vendor_payments.created_at', DB::raw("'vendor_payment' as type"), DB::raw("'vendor_payment' as source_type"), 'payment_number as ref', DB::raw('vendor_payments.amount * -1 as amount'), 'wallet_id', 'created_by as user_id', 'vendors.name_ar as detail', 'vendor_payments.reversed_at', 'vendor_payments.reversal_reason');

        $transfersOut = DB::table('wallet_transfers')
            ->leftJoin('wallets', 'wallet_transfers.to_wallet_id', '=', 'wallets.id')
            ->select('wallet_transfers.id', 'transfer_date as transaction_date', 'wallet_transfers.created_at', DB::raw("'transfer_out' as type"), DB::raw("'transfer' as source_type"), 'transfer_number as ref', DB::raw('wallet_transfers.amount * -1 as amount'), 'from_wallet_id as wallet_id', 'created_by as user_id', 'wallets.name as detail', 'wallet_transfers.reversed_at', 'wallet_transfers.reversal_reason');

        $transfersIn = DB::table('wallet_transfers')
            ->leftJoin('wallets', 'wallet_transfers.from_wallet_id', '=', 'wallets.id')
            ->select('wallet_transfers.id', 'transfer_date as transaction_date', 'wallet_transfers.created_at', DB::raw("'transfer_in' as type"), DB::raw("'transfer' as source_type"), 'transfer_number as ref', 'wallet_transfers.amount as amount', 'to_wallet_id as wallet_id', 'created_by as user_id', 'wallets.name as detail', 'wallet_transfers.reversed_at', 'wallet_transfers.reversal_reason');

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
        // فلترة حسب نوع الحركة — source_type بيوحّد الصادر/الوارد للتحويلات في قيمة واحدة "transfer"
        if ($request->filled('type') && array_key_exists($request->get('type'), self::REVERSIBLE_MODELS)) {
            $logsQuery->where('source_type', $request->get('type'));
        }
        // بحث نصي برقم المستند أو التفاصيل (اسم العميل/المورد/الفئة)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $logsQuery->where(function ($q) use ($search) {
                $q->where('ref', 'like', "%{$search}%")
                  ->orWhere('detail', 'like', "%{$search}%");
            });
        }

        // مجموع (in/out) + عدد السطور الكلي (للـ pagination) في استعلام واحد بدل ما يتحسبوا
        // في استعلامين منفصلين — الحركات المعكوسة بتدخل في العدّ الكلي (لسه ظاهرة في القائمة
        // للتدقيق) لكن مستبعدة من مجموع الوارد/المنصرف (أثرها المالي اتلغى) عبر CASE WHEN.
        $aggQuery = clone $logsQuery;
        $agg = $aggQuery->selectRaw('
            COUNT(*) as total_count,
            SUM(CASE WHEN reversed_at IS NULL AND amount > 0 THEN amount ELSE 0 END) as total_in,
            SUM(CASE WHEN reversed_at IS NULL AND amount < 0 THEN amount ELSE 0 END) as total_out
        ')->first();

        $totalIn = $agg->total_in ?? 0;
        $totalOut = abs($agg->total_out ?? 0);
        $totalCount = (int) ($agg->total_count ?? 0);

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

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $logsQuery->forPage($page, $perPage)->get();

        $logs = new LengthAwarePaginator($items, $totalCount, $perPage, $page, [
            'path'  => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);

        $walletIds = $logs->pluck('wallet_id')->unique();
        $userIds = $logs->pluck('user_id')->unique();

        $walletsMap = Wallet::whereIn('id', $walletIds)->pluck('name', 'id');
        $usersMap = User::whereIn('id', $userIds)->pluck('name', 'id');

        $logs->getCollection()->transform(function ($log) use ($walletsMap, $usersMap) {
            $log->wallet_name = $walletsMap[$log->wallet_id] ?? '—';
            $log->user_name = $usersMap[$log->user_id] ?? '—';
            return $log;
        });

        $type = $request->get('type', '');

        return view('financial_logs.index', compact('logs', 'totalIn', 'totalOut', 'sort', 'type'));
    }

    /**
     * عكس أثر عملية مالية بالكامل — بتفضل ظاهرة في السجل لكن بتتشال من رصيد المحفظة والإجماليات.
     */
    public function reverse(Request $request, string $sourceType, int $id)
    {
        $isAr = app()->getLocale() === 'ar';

        $modelClass = self::REVERSIBLE_MODELS[$sourceType] ?? null;
        abort_if($modelClass === null, 404);

        $data = $request->validate([
            'reversal_reason' => 'required|string|max:500',
        ]);

        $record = $modelClass::findOrFail($id);

        if ($record->isReversed()) {
            return back()->with('error', $isAr ? 'العملية معكوسة بالفعل.' : 'This operation is already reversed.');
        }

        DB::transaction(function () use ($record, $data) {
            $record->reverseOperation($data['reversal_reason'], auth()->id());
        });

        return back()->with('success', $isAr
            ? 'تم عكس العملية بنجاح — أثرها اتلغى بالكامل من رصيد المحفظة.'
            : 'Operation reversed successfully — its effect has been fully removed from the wallet balance.');
    }
}
