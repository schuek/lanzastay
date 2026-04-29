<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitacions', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('current_session_token');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('session_token')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['guest_email']);
            $table->dropColumn('guest_email');
        });

        Schema::table('habitacions', function (Blueprint $table) {
            $table->dropColumn('guest_email');
        });
    }
};
