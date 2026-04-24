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
    Schema::table('products', function (Blueprint $table) {
        $table->string('product_name')->after('product_id')->nullable();
        $table->integer('reorder_level')->default(5)->after('quantity');
        $table->enum('status', ['available', 'unavailable'])->default('available')->change();
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['product_name', 'reorder_level']);
    });
}
};
