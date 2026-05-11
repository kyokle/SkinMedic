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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id')->nullable();
        $table->decimal('total', 10, 2)->default(0);
        $table->text('note')->nullable();
        $table->enum('status', ['pending','confirmed','processing','ready_for_pickup','completed','cancelled'])->default('pending');
        $table->enum('payment_method', ['cash','gcash','bank_transfer'])->nullable();
        $table->enum('payment_status', ['unpaid','pending_verification','paid','refunded'])->default('unpaid');
        $table->string('payment_proof')->nullable();
        $table->string('reference', 100)->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
