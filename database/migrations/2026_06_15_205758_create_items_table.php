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
    Schema::create('items', function (Blueprint $table) {
        $table->id();
        $table->string('item_code')->unique(); // الحقل الأهم للتكويد [cite: 44]
        $table->string('barcode')->nullable(); // لدعم القراءة عبر أجهزة السكاينر [cite: 45]
        $table->string('name_ar'); // اسم الصنف باللغة العربية [cite: 46]
        $table->string('name_en')->nullable(); // اسم الصنف باللغة الإنجليزية [cite: 46]
        $table->string('item_group')->nullable(); // لربط الصنف بمجموعته الرئيسية [cite: 47]
        $table->string('sub_group')->nullable(); // لتصنيف أدق [cite: 48]
        $table->string('base_uom'); // أصغر وحدة يقاس بها الصنف في المخزن [cite: 50]
        
        // بيانات المخازن والرقابة
        $table->integer('reorder_point')->default(0); // الكمية التي عندها يرسل النظام تنبيهاً تلقائياً لطلب بضاعة [cite: 55]
        $table->integer('min_stock')->default(0); // لمنع نفاد البضاعة تماماً [cite: 57]
        $table->integer('max_stock')->nullable(); // لمنع تجميد السيولة في مخزون زائد [cite: 58]
        
        // المشتريات والموردين
        $table->foreignId('default_vendor_id')->nullable()->constrained('vendors')->nullOnDelete(); // المورد الرئيسي الذي يتم شراء هذا الصنف منه عادةً [cite: 63]
        $table->string('supplier_part_number')->nullable(); // كود الصنف عند المورد [cite: 64]
        $table->integer('moq')->default(1); // الحد الأدنى الذي يقبله المورد لشحن هذا الصنف [cite: 65]
        $table->integer('lead_time_days')->nullable(); // عدد الأيام التي يستغرقها المورد لتوصيل الصنف [cite: 66]
        
        // خصائص إضافية
        $table->enum('status', ['active', 'suspended', 'obsolete'])->default('active'); // حالة الصنف [cite: 68]
        $table->string('image_path')->nullable(); // صورة الصنف [cite: 69]
        $table->string('attachment_path')->nullable(); // لرفع ملفات الـ PDF الخاصة بكتالوج الصنف [cite: 70]
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
