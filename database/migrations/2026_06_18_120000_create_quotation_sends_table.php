<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('quote_number');                 // لقطة من رقم العرض وقت الإرسال
            $table->string('sent_to');                       // إيميل العميل المُرسل إليه
            $table->string('client_name')->nullable();       // اسم العميل وقت الإرسال
            $table->text('cc_emails')->nullable();           // إيميلات الإدارة (JSON) في الـ CC
            $table->string('sent_by')->nullable();           // اسم/إيميل المستخدم اللي بعت
            $table->string('subject')->nullable();           // عنوان الرسالة
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->timestamp('sent_at');                    // وقت الإرسال بالظبط
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_sends');
    }
};
