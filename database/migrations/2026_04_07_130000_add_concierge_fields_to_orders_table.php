<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('service_type')->default('comida')->after('room_number');
            $table->time('requested_time')->nullable()->after('service_type');
            $table->text('description')->nullable()->after('requested_time');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'requested_time', 'description']);
        });
    }
};
