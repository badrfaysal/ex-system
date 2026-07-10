<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تنفيذ الإضافة (Up)
     */
    public function up()
    {
        // 1. فهارس جدول العملاء
        Schema::table('clients', function (Blueprint $table) {
            $table->index('company_name'); // لتسريع البحث باسم العميل
            $table->index('phone');        // لتسريع البحث برقم الموبايل
            $table->index('client_type');  // لتسريع فلترة العملاء حسب نوعهم (جملة/تجزئة)
        });

        // 2. فهارس جدول الموردين
        Schema::table('vendors', function (Blueprint $table) {
            // ملاحظة: vendor_code مفهرس تلقائياً لأنه unique()
            $table->index('name_ar');      // لتسريع البحث باسم المورد
            $table->index('mobile');       // لتسريع البحث برقم هاتف المورد
            $table->index('vendor_group'); // لتسريع الفلترة (محلي/دولي)
            $table->index('status');       // لتسريع جلب الموردين النشطين فقط في القوائم المنسدلة
        });

        // 3. فهارس جدول الأصناف
        Schema::table('items', function (Blueprint $table) {
            // ملاحظة: item_code مفهرس تلقائياً لأنه unique()
            $table->index('barcode');      // حرج جداً لتسريع قراءة الباركود في المخازن والبحث
            $table->index('name_ar');      // لتسريع البحث باسم الصنف
            $table->index('item_group');   // لسرعة فلترة الأصناف حسب مجموعتها
            $table->index('status');       // لسرعة استبعاد الأصناف الملغاة من العمليات
        });
    }

    /**
     * التراجع عن الإضافة (Down) في حال أردت التراجع
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['company_name']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['client_type']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['mobile']);
            $table->dropIndex(['vendor_group']);
            $table->dropIndex(['status']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['item_group']);
            $table->dropIndex(['status']);
        });
    }
};