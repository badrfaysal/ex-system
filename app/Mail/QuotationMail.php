<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Mpdf\Mpdf;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation, public string $lang = 'ar') {}

    public function build(): static
    {
        $isAr = $this->lang === 'ar';

        $subject = $isAr
            ? 'عرض السعر رقم ' . $this->quotation->quote_number . ' — ' . config('mail.from.name')
            : 'Price Quotation No. ' . $this->quotation->quote_number . ' — ' . config('mail.from.name');

        $pdfBytes = $this->buildPdf($isAr);
        $filename = 'Quotation-' . $this->quotation->quote_number . '.pdf';

        return $this->subject($subject)
                    ->view('mail.quotation', ['quotation' => $this->quotation, 'isAr' => $isAr])
                    ->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
    }

    /**
     * توليد ملف الـ PDF عبر mPDF — يدعم العربي والـ RTL تلقائياً
     */
    private function buildPdf(bool $isAr): string
    {
        $this->quotation->load(['client', 'items']);

        $statusLabels = $isAr
            ? ['draft' => 'مسودة', 'sent' => 'مرسل', 'approved' => 'معتمد', 'rejected' => 'مرفوض',
               'converted' => 'محوّل', 'cancelled' => 'ملغي', 'expired' => 'منتهي الصلاحية']
            : ['draft' => 'Draft', 'sent' => 'Sent', 'approved' => 'Approved', 'rejected' => 'Rejected',
               'converted' => 'Converted', 'cancelled' => 'Cancelled', 'expired' => 'Expired'];

        $statusLabel = $statusLabels[$this->quotation->status] ?? $this->quotation->status;

        // مجلد كاش mpdf
        $cacheDir = storage_path('app/mpdf-tmp');
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'orientation'       => 'P',
            'tempDir'           => $cacheDir,
            'default_font'      => $isAr ? 'dejavusans' : 'dejavusans',
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'margin_left'       => 8,
            'margin_right'      => 8,
            'margin_top'        => 10,
            'margin_bottom'     => 10,
        ]);

        if ($isAr) {
            $mpdf->SetDirectionality('rtl');
        }

        $html = view('quotations.pdf', [
            'quotation'   => $this->quotation,
            'isAr'        => $isAr,
            'statusLabel' => $statusLabel,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
