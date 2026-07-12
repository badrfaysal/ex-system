<?php

namespace App\Exceptions;

use App\Models\Wallet;
use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(public Wallet $wallet, public float $requested, public float $available)
    {
        $isAr = app()->getLocale() === 'ar';

        parent::__construct($isAr
            ? "رصيد محفظة \"{$wallet->name}\" غير كافٍ. المتاح: " . number_format($available, 2) . " {$wallet->currency} — المطلوب: " . number_format($requested, 2) . " {$wallet->currency}."
            : "Insufficient balance in wallet \"{$wallet->name}\". Available: " . number_format($available, 2) . " {$wallet->currency} — requested: " . number_format($requested, 2) . " {$wallet->currency}.");
    }
}
