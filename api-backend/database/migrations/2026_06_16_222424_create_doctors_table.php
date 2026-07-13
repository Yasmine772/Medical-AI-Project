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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('specialization');
            $table->integer('years_of_experience');
            $table->string('clinic_phone')->nullable();
            $table->string('clinic_address')->nullable();
            $table->string('license_number')->nullable();
            $table->text('biography')->nullable();
            $table->string('photo')->nullable();
            $table->string('cv_file')->nullable();
            $table->string('license_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
