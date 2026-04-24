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
    Schema::create('appointments', function (Blueprint $table) {
        $table->increments('appointment_id');
        $table->unsignedInteger('user_id');
        $table->unsignedInteger('service_id')->nullable();
        $table->date('appointment_date');
        $table->time('appointment_time');
        $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
        $table->text('notes')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        $table->foreign('service_id')->references('service_id')->on('services')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::dropIfExists('appointments');
}
};
