<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('diagnosis_session_id')->constrained('diagnosis_sessions')->cascadeOnDelete();
            $table->string('stripe_payment_intent_id')->unique();
            $table->integer('amount');
            $table->string('currency', 3)->default('usd');
            $table->enum('status', ['pending', 'succeeded', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
