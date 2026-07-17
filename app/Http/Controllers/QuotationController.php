<?php

namespace App\Http\Controllers;

use App\Mail\QuotationMail;
use App\Models\Quotation;
use App\Models\QuotationSend;
use App\Models\Client;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QuotationController extends Controller
{
    /**
     * عرض قائمة عروض الأسعار
     */
    public function index(Request $request)
    {
        $query = Quotation::with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('opportunity_ref', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('quote_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('quote_date', '<=', $request->date_to);
        }

        $quotations = $query->latest()->paginate(10)->withQueryString();

        return view('quotations.index', compact('quotations'));
    }

    /**
     * سجل الإرسال — كل عرض سعر اتبعت: امتى، لمين، وبتفاصيله
     */
    public function sentLog(Request $request)
    {
        $query = QuotationSend::with('quotation');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('sent_to', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        $sends = $query->latest('sent_at')->paginate(15)->withQueryString();

        return view('quotations.sent_log', compact('sends'));
    }

    /**
     * شاشة إنشاء عرض سعر جديد
     */
    public function create()
    {
        $quotation = new Quotation([
            'quote_number' => $this->nextNumber(),
            'quote_date'   => now()->toDateString(),
            'expiry_date'  => now()->addDays(15)->toDateString(),
            'currency'     => 'EGP',
            'status'       => 'draft',
        ]);

        return view('quotations.create', $this->formData() + ['quotation' => $quotation]);
    }

    /**
     * حفظ عرض سعر جديد
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (empty($data['cost_center_name'])) {
            $data['cost_center_name'] = $this->suggestCostCenterName($data);
        }

        $quotation = DB::transaction(function () use ($data, $request) {
            $quotation = Quotation::create($this->headerData($data));
            $this->syncItems($quotation, $request);
            $this->recalcTotals($quotation, $data);
            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'تم حفظ عرض السعر بنجاح');
    }

    /**
     * عرض/طباعة عرض السعر
     */
    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'priceList', 'salesOrders', 'purchaseInvoices']);
        $salesOrder = $quotation->salesOrders->first();
        return view('quotations.show', compact('quotation', 'salesOrder'));
    }

    /**
     * شاشة تعديل عرض سعر
     */
    public function edit(Quotation $quotation)
    {
        if ($this->isLocked($quotation)) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', app()->getLocale() === 'ar'
                    ? 'لا يمكن تعديل عرض السعر بعد إرساله أو اعتماده.'
                    : 'Cannot edit a quotation after it has been sent or approved.');
        }

        $quotation->load('items');
        return view('quotations.edit', $this->formData() + ['quotation' => $quotation]);
    }

    /**
     * تحديث عرض سعر
     */
    public function update(Request $request, Quotation $quotation)
    {
        if ($this->isLocked($quotation)) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', app()->getLocale() === 'ar'
                    ? 'لا يمكن تعديل عرض السعر بعد إرساله أو اعتماده.'
                    : 'Cannot edit a quotation after it has been sent or approved.');
        }

        $data = $this->validateData($request, $quotation->id);

        // مايتمسحش الاسم لو اتسابت فاضية أثناء التعديل — يفضل زي ما كان
        if (empty($data['cost_center_name'])) {
            $data['cost_center_name'] = $quotation->cost_center_name;
        }

        DB::transaction(function () use ($data, $request, $quotation) {
            $quotation->update($this->headerData($data));
            $quotation->items()->delete();
            $this->syncItems($quotation, $request);
            $this->recalcTotals($quotation, $data);
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'تم تحديث عرض السعر بنجاح');
    }

    /**
     * هل عرض السعر مغلق للتعديل — لا يُعدَّل إلا وهو «مسودة» فقط
     */
    public static function isLocked(Quotation $quotation): bool
    {
        return $quotation->status !== 'draft';
    }

    /**
     * آلة الحالة — الانتقالات المسموح بها من كل حالة (لا رجوع للخلف)
     *  • مسودة (قيد المراجعة) → معتمد / مرفوض / ملغي
     *  • معتمد → محوّل / ملغي   (والإرسال يحوّلها إلى «مرسل» تلقائياً)
     *  • مرفوض → مسودة (لإعادة التجهيز) / ملغي
     *  • مرسل/محوّل/ملغي/منتهي = حالات نهائية لا تتغير
     */
    public const STATUS_FLOW = [
        'draft'     => ['approved', 'rejected', 'cancelled'],
        'approved'  => ['converted', 'cancelled'],
        'rejected'  => ['draft', 'cancelled'],
        'sent'      => [],
        'converted' => [],
        'cancelled' => [],
        'expired'   => [],
    ];

    /**
     * الحالات المسموح الانتقال إليها من الحالة الحالية
     */
    public static function allowedNext(?string $status): array
    {
        return self::STATUS_FLOW[$status] ?? [];
    }

    /**
     * نسخ عرض السعر (Clone)
     */
    public function clone(Quotation $quotation)
    {
        $quotation->load('items');

        $copy = DB::transaction(function () use ($quotation) {
            $new = $quotation->replicate(['quote_number']);
            $new->quote_number = $this->nextNumber();
            $new->status       = 'draft';
            $new->quote_date   = now()->toDateString();
            $new->expiry_date  = now()->addDays(15)->toDateString();
            $new->save();

            foreach ($quotation->items as $item) {
                $copyItem = $item->replicate(['quotation_id']);
                $copyItem->quotation_id = $new->id;
                $copyItem->save();
            }
            return $new;
        });

        return redirect()->route('quotations.edit', $copy)->with('success', 'تم نسخ عرض السعر، يمكنك التعديل والحفظ');
    }

    /**
     * تغيير حالة عرض السعر مباشرة من شاشة العرض
     */
    public function updateStatus(Request $request, Quotation $quotation)
    {
        $allowed = ['draft', 'under_review', 'sent', 'approved', 'rejected', 'converted', 'cancelled', 'expired'];
        $request->validate(['status' => 'required|in:' . implode(',', $allowed)]);

        $target = $request->status;
        $isAr   = app()->getLocale() === 'ar';

        // لا تغيير على الحالات النهائية
        if (empty(self::allowedNext($quotation->status))) {
            return back()->with('error', $isAr
                ? 'هذه الحالة نهائية ولا يمكن تغييرها.'
                : 'This is a final status and cannot be changed.');
        }

        // يُسمح فقط بالانتقالات المعرّفة في آلة الحالة (لا رجوع للخلف)
        if (!in_array($target, self::allowedNext($quotation->status), true)) {
            return back()->with('error', $isAr
                ? 'لا يمكن الانتقال إلى هذه الحالة من الحالة الحالية.'
                : 'This status transition is not allowed from the current status.');
        }

        $quotation->update(['status' => $target]);

        return back()->with('success', $isAr ? 'تم تحديث حالة عرض السعر بنجاح' : 'Quotation status updated successfully');
    }

    /**
     * إرسال عرض السعر بالبريد الإلكتروني
     */
    public function sendEmail(Request $request, Quotation $quotation)
    {
        $quotation->load(['client', 'items']);

        $locale = app()->getLocale();

        // لا يُسمح بالإرسال إلا بعد المراجعة والاعتماد (الحالة = معتمد)
        if ($quotation->status !== 'approved') {
            return back()->with('error', $locale === 'ar'
                ? 'لم تتم مراجعة واعتماد العرض بعد — يجب أن تكون الحالة «معتمد» قبل الإرسال.'
                : 'The quotation has not been reviewed and approved yet — its status must be «Approved» before sending.');
        }

        $toEmail = optional($quotation->client)->email;

        if (!$toEmail) {
            return back()->with('error', $locale === 'ar'
                ? 'لا يوجد بريد إلكتروني مسجل لهذا العميل.'
                : 'No email address is registered for this client.');
        }

        // إيميلات إشعار الإدارة (CC) — من الإعدادات
        $ccEmails = Setting::where('category', 'notify_email')
            ->pluck('key_value')
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->reject(fn ($e) => strtolower($e) === strtolower($toEmail)) // تجنب التكرار مع العميل
            ->values()
            ->all();

        try {
            $mailer = Mail::to($toEmail);
            if (!empty($ccEmails)) {
                $mailer->cc($ccEmails);
            }
            $mailer->send(new QuotationMail($quotation, lang: $locale));

            // تسجيل عملية الإرسال في السجل
            QuotationSend::create([
                'quotation_id' => $quotation->id,
                'quote_number' => $quotation->quote_number,
                'sent_to'      => $toEmail,
                'client_name'  => optional($quotation->client)->displayName($locale),
                'cc_emails'    => $ccEmails,
                'sent_by'      => optional(Auth::user())->name ?? optional(Auth::user())->email,
                'subject'      => $locale === 'ar'
                    ? 'عرض السعر رقم ' . $quotation->quote_number
                    : 'Price Quotation No. ' . $quotation->quote_number,
                'grand_total'  => $quotation->grand_total,
                'currency'     => $quotation->currency,
                'sent_at'      => now(),
            ]);

            // بعد الإرسال الناجح تصبح الحالة «مرسل» (نهائية)
            $quotation->update(['status' => 'sent']);

            $ccNote = !empty($ccEmails)
                ? ($locale === 'ar'
                    ? ' (ونسخة إلى ' . count($ccEmails) . ' من الإدارة)'
                    : ' (with CC to ' . count($ccEmails) . ' management)')
                : '';

            return back()->with('success',
                $locale === 'ar'
                    ? 'تم إرسال عرض السعر بنجاح إلى ' . $toEmail . $ccNote
                    : 'Quotation sent successfully to ' . $toEmail . $ccNote
            );
        } catch (\Throwable $e) {
            return back()->with('error',
                $locale === 'ar'
                    ? 'فشل إرسال البريد: ' . $e->getMessage()
                    : 'Mail send failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * AJAX: جلب أصناف قائمة أسعار مع أسعارها
     */
    public function priceListItems(PriceList $priceList)
    {
        $items = $priceList->items()->with('item')->get()->map(function ($pli) {
            return [
                'item_id'     => $pli->item_id,
                'item_code'   => optional($pli->item)->item_code,
                'description' => optional($pli->item)->name_ar,
                'uom'         => optional($pli->item)->base_uom,
                'list_price'  => (float) $pli->price,
            ];
        });

        return response()->json([
            'currency' => $priceList->default_currency,
            'items'    => $items,
        ]);
    }

    /* ===================== Helpers ===================== */

    private function formData(): array
    {
        $lookups = \Illuminate\Support\Facades\Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return [
            'clients'    => Client::orderBy('company_name')->get(),
            'items'      => Item::orderBy('name_ar')->get(),
            'priceLists' => PriceList::where('status', 'active')->orderBy('name')->get(),
            'currencies' => $lookups->get('currency') ?? collect(),
            'uoms'       => ($lookups->get('uom') ?? collect())->pluck('display_name', 'key_value'),
        ];
    }

    private function nextNumber(): string
    {
        $last = Quotation::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'QT-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function validateData(Request $request, $ignoreId = null)
    {
        return $request->validate([
            'quote_number'             => 'required|string|unique:quotations,quote_number' . ($ignoreId ? ",$ignoreId" : ''),
            'quote_date'               => 'required|date',
            'expiry_date'              => 'nullable|date|after_or_equal:quote_date',
            'opportunity_ref'          => 'nullable|string',
            'client_id'                => 'required|exists:clients,id',
            'price_list_id'            => 'nullable|exists:price_lists,id',
            'sales_rep'                => 'nullable|string',
            'currency'                 => 'required|string',
            'cost_center_name'         => 'nullable|string|max:255',
            'status'                   => 'nullable|string',
            'terms'                    => 'nullable|string',
            'extra_discount'           => 'nullable|numeric|min:0',
            'items'                    => 'required|array|min:1',
            'items.*.description'      => 'required|string',
            'items.*.item_id'          => 'nullable|exists:items,id',
            'items.*.quantity'         => 'required|numeric|min:0',
            'items.*.uom'              => 'nullable|string',
            'items.*.list_price'       => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percent'      => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function headerData(array $data): array
    {
        return [
            'quote_number'      => $data['quote_number'],
            'quote_date'        => $data['quote_date'],
            'expiry_date'       => $data['expiry_date'] ?? null,
            'opportunity_ref'   => $data['opportunity_ref'] ?? null,
            'cost_center_name'  => $data['cost_center_name'] ?? null,
            'client_id'         => $data['client_id'],
            'price_list_id'     => $data['price_list_id'] ?? null,
            'sales_rep'         => $data['sales_rep'] ?? null,
            'currency'          => $data['currency'],
            'status'            => $data['status'] ?? 'draft',
            'terms'             => $data['terms'] ?? null,
        ];
    }

    /**
     * اقتراح اسم افتراضي لمركز التكلفة لو المستخدم سابه فاضي — «مركز تكلفة العميل X بتاريخ Y»
     */
    private function suggestCostCenterName(array $data): string
    {
        $client = Client::find($data['client_id']);
        $clientName = $client ? $client->displayName('ar') : '';
        $date = \Illuminate\Support\Carbon::parse($data['quote_date'])->format('Y-m-d');

        return trim("مركز تكلفة العميل {$clientName} بتاريخ {$date}");
    }

    /**
     * حفظ صفوف الأصناف وحساب صافي كل سطر
     */
    private function syncItems(Quotation $quotation, Request $request)
    {
        foreach ($request->input('items', []) as $row) {
            if (empty($row['description'])) {
                continue;
            }

            $qty      = (float) ($row['quantity'] ?? 0);
            $price    = (float) ($row['list_price'] ?? 0);
            $discount = (float) ($row['discount_percent'] ?? 0);
            $tax      = (float) ($row['tax_percent'] ?? 0);

            $lineBase  = $qty * $price;
            $afterDisc = $lineBase - ($lineBase * $discount / 100);
            $netTotal  = $afterDisc + ($afterDisc * $tax / 100);

            $quotation->items()->create([
                'item_id'          => $row['item_id'] ?? null,
                'item_code'        => $row['item_code'] ?? null,
                'description'      => $row['description'],
                'quantity'         => $qty,
                'uom'              => $row['uom'] ?? null,
                'list_price'       => $price,
                'discount_percent' => $discount,
                'tax_percent'      => $tax,
                'net_total'        => round($netTotal, 2),
            ]);
        }
    }

    /**
     * إعادة حساب إجماليات العرض من الأصناف المحفوظة
     */
    private function recalcTotals(Quotation $quotation, array $data)
    {
        $quotation->load('items');

        $subtotal      = 0; // مجموع (كمية × سعر) قبل أي خصم/ضريبة
        $lineDiscounts = 0; // مجموع خصومات الأسطر
        $taxAmount     = 0; // مجموع الضرائب

        foreach ($quotation->items as $line) {
            $lineBase  = $line->quantity * $line->list_price;
            $discVal   = $lineBase * $line->discount_percent / 100;
            $afterDisc = $lineBase - $discVal;
            $taxVal    = $afterDisc * $line->tax_percent / 100;

            $subtotal      += $lineBase;
            $lineDiscounts += $discVal;
            $taxAmount     += $taxVal;
        }

        $extraDiscount  = (float) ($data['extra_discount'] ?? 0);
        $totalDiscount  = $lineDiscounts + $extraDiscount;
        $grandTotal     = $subtotal - $totalDiscount + $taxAmount;

        $quotation->update([
            'subtotal'       => round($subtotal, 2),
            'extra_discount' => round($extraDiscount, 2),
            'total_discount' => round($totalDiscount, 2),
            'tax_amount'     => round($taxAmount, 2),
            'grand_total'    => round($grandTotal, 2),
        ]);
    }
}
