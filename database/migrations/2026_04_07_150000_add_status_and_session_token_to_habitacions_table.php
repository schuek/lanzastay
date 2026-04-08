<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->string('status')->default('disponible')->after('activa');
            $table->string('current_session_token')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->dropColumn(['status', 'current_session_token']);
        });
    }
};
