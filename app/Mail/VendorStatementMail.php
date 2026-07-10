<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;

class VendorStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vendor $vendor,
        public Collection $timeline,
        public float $balance,
        public string $lang = 'ar'
    ) {}

    public function build(): static
    {
        $isAr = $this->lang === 'ar';
        $vendorName = $isAr ? $this->vendor->name_ar : ($this->vendor->name_en ?: $this->vendor->name_ar);

        $subject = $isAr
            ? 'كشف حساب — ' . $vendorName . ' — ' . config('mail.from.name')
            : 'Account Statement — ' . $vendorName . ' — ' . config('mail.from.name');

        $pdfBytes = $this->buildPdf($isAr, $vendorName);
        $filename = 'Statement-' . str_replace(' ', '-', $vendorName) . '.pdf';

        return $this->subject($subject)
            ->view('mail.vendor_statement', ['vendor' => $this->vendor, 'vendorName' => $vendorName, 'balance' => $this->balance, 'isAr' => $isAr])
            ->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
    }

    private function buildPdf(bool $isAr, string $vendorName): string
    {
        $cacheDir = storage_path('app/mpdf-tmp');
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

        $mpdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => 'P',
            'tempDir'          => $cacheDir,
            'default_font'     => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'margin_left'      => 8,
            'margin_right'     => 8,
            'margin_top'       => 10,
            'margin_bottom'    => 10,
        ]);

        if ($isAr) {
            $mpdf->SetDirectionality('rtl');
        }

        $html = view('statements.vendor_pdf', [
            'vendor'     => $this->vendor,
            'vendorName' => $vendorName,
            'timeline'   => $this->timeline,
            'balance'    => $this->balance,
            'isAr'       => $isAr,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
