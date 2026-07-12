<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_counters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // VP, EXP, RC, TR, REV...
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        // نبدأ العداد بعد آخر رقم مُستخدم فعليًا في كل جدول، عشان مايحصلش
        // تعارض مع أرقام مستندات موجودة بالفعل.
        $now = now();
        $seeds = [
            'VP'  => DB::table('vendor_payments')->max('id'),
            'EXP' => DB::table('expenses')->max('id'),
            'RC'  => DB::table('client_receipts')->max('id'),
            'TR'  => DB::table('wallet_transfers')->max('id'),
            'REV' => DB::table('revenues')->max('id'),
            'SO'  => DB::table('sales_orders')->max('id'),
            'SI'  => DB::table('sales_invoices')->max('id'),
            'PI'  => DB::table('purchase_invoices')->max('id'),
        ];

        foreach ($seeds as $key => $maxId) {
            DB::table('document_counters')->insert([
                'key'          => $key,
                'next_number'  => ($maxId ?? 0) + 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_counters');
    }
};
