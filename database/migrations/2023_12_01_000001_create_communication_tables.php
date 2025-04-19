<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de mensajes
        Schema::create('communication_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('appointment_id');
            $table->string('patient_id');
            $table->string('phone_number');
            $table->text('content');
            $table->string('message_type'); // whatsapp, sms
            $table->string('status'); // pending, sent, delivered, read, failed
            $table->string('message_id')->nullable();
            $table->text('message_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('message_id');
        });

        // Tabla de llamadas
        Schema::create('communication_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('appointment_id');
            $table->string('patient_id');
            $table->string('phone_number');
            $table->string('status'); // pending, initiated, in_progress, completed, confirmed, cancelled, failed, no_answer
            $table->string('call_type'); // appointment_reminder, appointment_confirmation, appointment_cancellation
            $table->string('call_id')->nullable();
            $table->string('flow_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('call_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_calls');
        Schema::dropIfExists('communication_messages');
    }
};
