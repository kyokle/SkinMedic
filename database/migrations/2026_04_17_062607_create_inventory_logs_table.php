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
    Schema::create('inventory_logs', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('product_id');
        $table->integer('quantity')->default(0);
        $table->enum('type', ['IN', 'OUT']);
        $table->date('expiry_date')->nullable();
        $table->unsignedInteger('appointment_id')->nullable();
        $table->timestamps();

        $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('inventory_logs');
}
};
