<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->json('patient_data')->nullable()->after('doctor_notes');
            $table->json('symptoms')->nullable()->after('patient_data');
            $table->json('ai_result')->nullable()->after('symptoms');
            $table->json('tips')->nullable()->after('ai_result');
            $table->string('pdf_url')->nullable()->after('tips');
            $table->boolean('doctor_edited')->default(false)->after('pdf_url');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'patient_data',
                'symptoms',
                'ai_result',
                'tips',
                'pdf_url',
                'doctor_edited',
            ]);
        });
    }
};
