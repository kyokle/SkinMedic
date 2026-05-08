<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_waitlist', function (Blueprint $table) {
            $table->id('waitlist_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('service_id');
            $table->date('preferred_date');
            $table->time('preferred_time');
            $table->enum('status', ['waiting', 'notified', 'claimed', 'expired', 'skipped'])
                  ->default('waiting');
            $table->string('claim_token')->nullable()->unique();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('claim_expires_at')->nullable();
            $table->integer('queue_position')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_waitlist');
    }
};