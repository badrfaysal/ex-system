<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_code', 50)->nullable()->unique()->after('id');
        });

        // ملء الأكواد التلقائية للعملاء الحاليين
        $clients = DB::table('clients')->orderBy('id')->get();
        foreach ($clients as $client) {
            DB::table('clients')->where('id', $client->id)->update([
                'client_code' => 'CLT-' . str_pad($client->id, 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('client_code');
        });
    }
};
