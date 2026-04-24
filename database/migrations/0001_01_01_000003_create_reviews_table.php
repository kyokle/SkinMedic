<?php
// database/migrations/xxxx_xx_xx_create_reviews_table.php
// Run with: php artisan migrate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
                $table->bigIncrements('review_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('appointment_id')->unique();
                $table->unsignedInteger('service_id')->nullable();
                $table->unsignedTinyInteger('rating');
                $table->text('comment');
                $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};