<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'default_currency', 'valid_from', 'valid_to', 'status', 'notes',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];

    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }
}
