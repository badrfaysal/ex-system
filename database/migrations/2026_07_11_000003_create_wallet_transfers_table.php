<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();                // TR-2026-07-0001
            $table->foreignId('from_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('to_wallet_id')->constrained('wallets')->restrictOnDelete();

            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->default('EGP');
            $table->date('transfer_date');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('from_wallet_id');
            $table->index('to_wallet_id');
            $table->index('transfer_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transfers');
    }
};
