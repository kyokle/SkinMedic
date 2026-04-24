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
    Schema::create('service_products', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('service_id');
        $table->unsignedInteger('product_id');
        $table->integer('quantity_used')->default(1);
        $table->timestamps();

        $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');
        $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('service_products');
}
};
