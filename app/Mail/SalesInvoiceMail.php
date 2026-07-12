<?php

namespace App\Mail;

use App\Models\SalesInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Mpdf\Mpdf;

class SalesInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SalesInvoice $salesInvoice, public string $lang = 'ar') {}

    public function build(): static
    {
        $isAr = $this->lang === 'ar';

        $subject = $isAr
            ? 'فاتورة بيع رقم ' . $this->salesInvoice->invoice_number . ' — ' . config('mail.from.name')
            : 'Sales Invoice No. ' . $this->salesInvoice->invoice_number . ' — ' . config('mail.from.name');

        $pdfBytes = $this->buildPdf($isAr);
        $filename = 'SalesInvoice-' . $this->salesInvoice->invoice_number . '.pdf';

        return $this->subject($subject)
                    ->view('mail.sales_invoice', ['salesInvoice' => $this->salesInvoice, 'isAr' => $isAr])
                    ->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
    }

    private function buildPdf(bool $isAr): string
    {
        $this->salesInvoice->load(['client', 'items']);

        // Cache dir for mPDF
        $cacheDir = storage_path('app/mpdf-tmp');
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'orientation'       => 'P',
            'tempDir'           => $cacheDir,
            'default_font'      => 'dejavusans',
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

        $html = view('sales_invoices.pdf', [
            'salesInvoice' => $this->salesInvoice,
            'isAr'         => $isAr,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
