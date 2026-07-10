<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationSend extends Model
{
    protected $fillable = [
        'quotation_id', 'quote_number', 'sent_to', 'client_name',
        'cc_emails', 'sent_by', 'subject', 'grand_total', 'currency', 'sent_at',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'cc_emails'   => 'array',
        'grand_total' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
