<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * ملاحظ عام لسجل العمليات — بيتسجّل على الموديلات الأساسية في AppServiceProvider.
 * بيسجّل بس لو فيه يوزر مسجّل دخول، عشان السيدر ما يوسخش السجل بحركات وهمية.
 */
class ActivityObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model);
    }

    public function updated(Model $model): void
    {
        $this->log('updated', $model);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }

    private function log(string $action, Model $model): void
    {
        if (!Auth::check()) {
            return;
        }

        ActivityLog::create([
            'user_id'       => Auth::id(),
            'action'        => $action,
            'subject_type'  => class_basename($model),
            'subject_id'    => $model->getKey(),
            'subject_label' => $this->labelFor($model),
        ]);
    }

    private function labelFor(Model $model): ?string
    {
        foreach (['invoice_number', 'quote_number', 'so_number', 'receipt_number', 'payment_number', 'transfer_number', 'expense_number', 'name', 'name_ar', 'company_name', 'item_code'] as $field) {
            if (!empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return null;
    }
}
