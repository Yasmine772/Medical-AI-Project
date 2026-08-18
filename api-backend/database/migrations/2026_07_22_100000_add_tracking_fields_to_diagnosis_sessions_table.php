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
                'doctor_review',
                'report_ready',
                'completed',
            ])->default('doctor_review')->after('status');

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
                'doctor_reviewed_at',
                'report_generated_at',
                'doctor_notes',
            ]);
        });
    }
};
