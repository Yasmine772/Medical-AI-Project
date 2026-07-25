<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->enum('phase', [
                'payment_pending',
                'ai_analysis',
                'doctor_review',
                'report_ready',
                'completed',
            ])->default('payment_pending')->after('status');

            $table->timestamp('payment_confirmed_at')->nullable();
            $table->timestamp('ai_analysis_completed_at')->nullable();
            $table->timestamp('doctor_reviewed_at')->nullable();
            $table->timestamp('report_generated_at')->nullable();
            $table->text('doctor_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'phase',
                'payment_confirmed_at',
                'ai_analysis_completed_at',
                'doctor_reviewed_at',
                'report_generated_at',
                'doctor_notes',
            ]);
        });
    }
};
