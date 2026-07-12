<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');                    // created / updated / deleted
            $table->string('subject_type');               // اسم الموديل، زي Quotation
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();  // مرجع نصي (رقم الفاتورة مثلاً) — يفضل موجود حتى لو السجل اتمسح
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
