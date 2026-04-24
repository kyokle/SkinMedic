<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor', function (Blueprint $table) {
            $table->string('profile_picture')->default('default.png')->after('specialization');
            $table->integer('years_of_experience')->nullable()->after('profile_picture');
            $table->string('license_number')->nullable()->unique()->after('years_of_experience');
            $table->json('availability_schedule')->nullable()->after('license_number');
        });
    }

    public function down(): void
    {
        Schema::table('doctor', function (Blueprint $table) {
            $table->dropColumn([
                'profile_picture',
                'years_of_experience',
                'license_number',
                'availability_schedule',
            ]);
        });
    }
};
