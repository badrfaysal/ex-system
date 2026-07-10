<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;

class ClientStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public Collection $timeline,
        public float $balance,
        public string $lang = 'ar'
    ) {}

    public function build(): static
    {
        $isAr = $this->lang === 'ar';

        $subject = $isAr
            ? 'كشف حساب — ' . $this->client->displayName('ar') . ' — ' . config('mail.from.name')
            : 'Account Statement — ' . $this->client->displayName('en') . ' — ' . config('mail.from.name');

        $pdfBytes = $this->buildPdf($isAr);
        $filename = 'Statement-' . str_replace(' ', '-', $this->client->displayName($isAr ? 'ar' : 'en')) . '.pdf';

        return $this->subject($subject)
            ->view('mail.client_statement', ['client' => $this->client, 'balance' => $this->balance, 'isAr' => $isAr])
            ->attachData($pdfBytes, $filename, ['mime' => 'application/pdf']);
    }

    private function buildPdf(bool $isAr): string
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

        $html = view('statements.client_pdf', [
            'client'   => $this->client,
            'timeline' => $this->timeline,
            'balance'  => $this->balance,
            'isAr'     => $isAr,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
