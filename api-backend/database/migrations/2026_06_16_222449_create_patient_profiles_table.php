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
        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->boolean('is_smoker')->default(false);
            $table->boolean('has_diabetes')->default(false);
            $table->boolean('has_hypertension')->default(false);
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('drinks_alcohol')->default(false);
            $table->string('occupation')->nullable();
            $table->enum('activity_level', ['sedentary', 'moderate', 'active'])->nullable();
            $table->timestamp('last_checkup_date');
            //setting for user preferences
            $table->boolean('notifications_enabled')->default(true);
            $table->string('theme')->default('light');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_profiles');
    }
};
