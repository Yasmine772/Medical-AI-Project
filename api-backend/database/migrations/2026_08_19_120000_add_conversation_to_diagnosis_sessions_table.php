<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted values are long base64 strings, so columns holding encrypted
     * casts must be TEXT (json rejects them). Mirrors the ai_result column.
     */
    public function up(): void
    {
        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->text('conversation')->nullable()->after('ai_result');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_sessions', function (Blueprint $table) {
            $table->dropColumn('conversation');
        });
    }
};
