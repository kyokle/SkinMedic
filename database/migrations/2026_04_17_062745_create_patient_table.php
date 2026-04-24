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
    Schema::create('patient', function (Blueprint $table) {
        $table->increments('patient_id');
        $table->unsignedInteger('user_id');
        $table->string('profile_picture')->default('uploads/default.png')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('patient');
}
};
