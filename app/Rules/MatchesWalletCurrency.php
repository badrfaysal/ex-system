<?php

namespace App\Rules;

use App\Models\Wallet;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * تتأكد إن عملة الحركة == عملة المحفظة المختارة.
 * المحفظة تعمل بعملة واحدة فقط، فأي حركة بعملة مختلفة مرفوضة.
 */
class MatchesWalletCurrency implements ValidationRule, DataAwareRule
{
    /** @var array<string,mixed> */
    protected array $data = [];

    public function __construct(protected string $walletField = 'wallet_id')
    {
    }

    /** @param array<string,mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $walletId = $this->data[$this->walletField] ?? null;

        // غياب المحفظة أو عدم وجودها بيتكفّل بيه required/exists في نفس الطلب
        if (! $walletId) {
            return;
        }

        $wallet = Wallet::find($walletId);
        if (! $wallet) {
            return;
        }

        if ((string) $value !== (string) $wallet->currency) {
            $isAr = app()->getLocale() === 'ar';
            $fail($isAr
                ? "هذه المحفظة تعمل بعملة {$wallet->currency} فقط، ولا يمكن تسجيل حركة بعملة مختلفة."
                : "This wallet operates in {$wallet->currency} only; a transaction in a different currency is not allowed.");
        }
    }
}
