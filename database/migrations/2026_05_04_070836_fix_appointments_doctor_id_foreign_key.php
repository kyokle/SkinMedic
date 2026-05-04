<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->foreign('doctor_id')
                  ->references('doctor_id')
                  ->on('doctor')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->foreign('doctor_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
};