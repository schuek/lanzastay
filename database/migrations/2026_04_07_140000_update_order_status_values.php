<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'pending')->update(['status' => 'recibido']);
        DB::table('orders')->whereIn('status', ['preparing', 'delivering'])->update(['status' => 'en_proceso']);
        DB::table('orders')->where('status', 'completed')->update(['status' => 'completado']);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('recibido')->change();
        });
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'recibido')->update(['status' => 'pending']);
        DB::table('orders')->where('status', 'en_proceso')->update(['status' => 'preparing']);
        DB::table('orders')->where('status', 'completado')->update(['status' => 'completed']);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
