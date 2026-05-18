<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->decimal('precio_noche', 10, 2)->nullable()->after('guest_email');
            $table->timestamp('check_in_at')->nullable()->after('precio_noche');
        });
    }

    public function down(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->dropColumn(['precio_noche', 'check_in_at']);
        });
    }
};
