<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted values are long base64 strings, so columns holding encrypted
     * casts must be TEXT (not integer / json which reject them).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('otp')->nullable()->change();
            $table->text('fcm_token')->nullable()->change();
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->text('gender')->nullable()->change();
            $table->text('activity_level')->nullable()->change();
            $table->text('blood_type')->nullable()->change();
            $table->text('occupation')->nullable()->change();
        });

        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->text('patient_data')->nullable()->change();
            $table->text('symptoms')->nullable()->change();
            $table->text('ai_result')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('otp')->nullable()->change();
            $table->string('fcm_token')->nullable()->change();
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->change();
            $table->enum('activity_level', ['sedentary', 'moderate', 'active'])->nullable()->change();
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable()->change();
            $table->string('occupation')->nullable()->change();
        });

        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->json('patient_data')->nullable()->change();
            $table->json('symptoms')->nullable()->change();
            $table->json('ai_result')->nullable()->change();
        });
    }
};
