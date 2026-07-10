<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('vendor_code')->unique(); // توليد تلقائي أو إدخال يدوي [cite: 3]
        $table->string('name_ar'); // الاسم المعروف به في السوق [cite: 4]
        $table->string('name_en')->nullable(); // [cite: 5]
        $table->string('legal_name')->nullable(); // الاسم المكتوب في السجل التجاري [cite: 6]
        $table->string('vendor_group')->nullable(); // لتصنيف المورد [cite: 7]
        $table->enum('status', ['active', 'on_hold', 'blocked'])->default('active'); // حالة المورد [cite: 9]
        $table->text('block_reason')->nullable(); // كتابة السبب إذا تم حظر المورد [cite: 9]
        
        // بيانات الاتصال [cite: 13]
        $table->string('phone')->nullable(); 
        $table->string('mobile')->nullable();
        $table->string('email')->nullable();
        $table->string('website')->nullable();
        
        // مسؤول الاتصال الرئيسي [cite: 17]
        $table->string('contact_person_name')->nullable(); 
        $table->string('contact_person_job')->nullable();
        $table->string('contact_person_mobile')->nullable();
        $table->string('contact_person_email')->nullable();
        
        // البيانات المالية والضرائب
        $table->string('default_currency')->default('EGP'); // لتقييم فروق العملة تلقائياً [cite: 21]
        $table->string('tax_id')->nullable(); // حقل حاسم وحتمي لإصدار الفواتير الإلكترونية [cite: 22]
        $table->string('commercial_registry')->nullable(); // رقم السجل التجاري [cite: 23]
        $table->decimal('credit_limit', 15, 2)->nullable(); // أقصى مديونية يسمح بها المورد [cite: 24]
        
        // شروط الدفع واللوجستيات
        $table->string('payment_terms')->nullable(); // طريقة السداد المتفق عليها [cite: 29]
        $table->string('payment_method')->nullable(); // طريقة الدفع المفضلة [cite: 30]
        $table->string('bank_name')->nullable(); // بيانات الحساب البنكي [cite: 31]
        $table->string('bank_branch')->nullable(); // [cite: 31]
        $table->string('account_holder')->nullable(); // [cite: 31]
        $table->string('account_number')->nullable(); // [cite: 31]
        $table->string('iban')->nullable(); // [cite: 31]
        $table->string('swift_code')->nullable(); // [cite: 31]
        
        $table->integer('lead_time_days')->nullable(); // متوسط عدد الأيام التي يستغرقها المورد لتسليم البضاعة [cite: 32]
        $table->decimal('vendor_rating', 3, 2)->nullable(); // درجة تقييم المورد [cite: 36]
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
