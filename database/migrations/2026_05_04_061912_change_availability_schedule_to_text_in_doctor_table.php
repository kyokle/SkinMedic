<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctor', function (Blueprint $table) {
            $table->text('availability_schedule')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('doctor', function (Blueprint $table) {
            $table->longText('availability_schedule')->nullable()->change();
        });
    }
};