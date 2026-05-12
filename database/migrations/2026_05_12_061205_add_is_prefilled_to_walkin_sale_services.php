<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('walkin_sale_services', function (Blueprint $table) {
        $table->boolean('is_prefilled')
              ->default(0)
              ->after('service_price')
              ->comment('1 = came from a completed appointment (not charged here); 0 = new add-on (charged)');
    });
}

public function down(): void
{
    Schema::table('walkin_sale_services', function (Blueprint $table) {
        $table->dropColumn('is_prefilled');
    });
}
};
