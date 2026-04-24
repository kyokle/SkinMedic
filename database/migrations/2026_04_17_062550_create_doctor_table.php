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
    Schema::create('doctor', function (Blueprint $table) {
        $table->increments('doctor_id');
        $table->unsignedInteger('user_id');
        $table->string('specialization')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('doctor');
}
};
