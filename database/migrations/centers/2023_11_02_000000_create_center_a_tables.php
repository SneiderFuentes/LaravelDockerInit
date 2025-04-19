<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql_center_a';

    public function up(): void
    {
        // Tabla de pacientes
        Schema::connection($this->connection)->create('pacientes', function (Blueprint $table) {
            $table->id('id_paciente');
            $table->string('nombre_completo');
            $table->string('telefono');
            $table->string('email')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['M', 'F', 'O'])->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // Tabla de citas
        Schema::connection($this->connection)->create('citas_programadas', function (Blueprint $table) {
            $table->id('id_cita');
            $table->unsignedBigInteger('paciente_ref');
            $table->dateTime('fecha_hora');
            $table->string('estado')->default('pending');
            $table->string('tipo_cita')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('paciente_ref')
                ->references('id_paciente')
                ->on('pacientes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('citas_programadas');
        Schema::connection($this->connection)->dropIfExists('pacientes');
    }
};
