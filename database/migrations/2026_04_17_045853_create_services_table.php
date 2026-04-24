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
    Schema::create('services', function (Blueprint $table) {
        $table->increments('service_id');
        $table->string('name');
        $table->text('description');
        $table->decimal('price', 8, 2);
        $table->string('image')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('services');
}
};
