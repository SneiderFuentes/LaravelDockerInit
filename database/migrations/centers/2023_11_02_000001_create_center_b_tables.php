<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_center_b';

    public function up(): void
    {
        // Tabla de pacientes
        Schema::connection($this->connection)->create('patients_b', function (Blueprint $table) {
            $table->id('patient_id');
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabla de citas
        Schema::connection($this->connection)->create('appointments_b', function (Blueprint $table) {
            $table->uuid('appt_id')->primary();
            $table->unsignedBigInteger('p_id');
            $table->dateTime('scheduled_on');
            $table->string('status_code')->default('pending');
            $table->string('service_type')->nullable();
            $table->unsignedBigInteger('practitioner_id')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();

            $table->foreign('p_id')
                ->references('patient_id')
                ->on('patients_b')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('appointments_b');
        Schema::connection($this->connection)->dropIfExists('patients_b');
    }
};
