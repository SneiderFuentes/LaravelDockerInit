<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DateTime;

class CentersTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCenterA();
        $this->seedCenterB();
    }

    private function seedCenterA(): void
    {
        // Insertar pacientes
        $patientIds = [];
        foreach (range(1, 10) as $i) {
            $id = DB::connection('mysql_center_a')->table('pacientes')->insertGetId([
                'nombre_completo' => "Paciente Prueba {$i}",
                'telefono' => "57300000000{$i}",
                'email' => "paciente{$i}@example.com",
                'fecha_nacimiento' => new DateTime("1980-01-{$i}"),
                'genero' => $i % 2 === 0 ? 'M' : 'F',
                'observaciones' => "Datos de prueba para paciente {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $patientIds[] = $id;
        }

        // Insertar citas
        foreach ($patientIds as $patientId) {
            // Una cita pendiente
            DB::connection('mysql_center_a')->table('citas_programadas')->insert([
                'paciente_ref' => $patientId,
                'fecha_hora' => now()->addDays(rand(1, 14))->setTime(rand(8, 17), 0),
                'estado' => 'pending',
                'tipo_cita' => 'consulta',
                'doctor_id' => rand(1, 5),
                'observaciones' => 'Cita pendiente de confirmación',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Una cita confirmada
            DB::connection('mysql_center_a')->table('citas_programadas')->insert([
                'paciente_ref' => $patientId,
                'fecha_hora' => now()->addDays(rand(15, 30))->setTime(rand(8, 17), 0),
                'estado' => 'confirmed',
                'tipo_cita' => 'control',
                'doctor_id' => rand(1, 5),
                'observaciones' => 'Cita ya confirmada',
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedCenterB(): void
    {
        // Insertar pacientes
        $patientIds = [];
        foreach (range(1, 10) as $i) {
            $id = DB::connection('mysql_center_b')->table('patients_b')->insertGetId([
                'full_name' => "Test Patient {$i}",
                'phone_number' => "57300111000{$i}",
                'email' => "patient{$i}@example.com",
                'birth_date' => new DateTime("1985-01-{$i}"),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'notes' => "Test data for patient {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $patientIds[] = $id;
        }

        // Insertar citas
        foreach ($patientIds as $patientId) {
            // Una cita pendiente
            DB::connection('mysql_center_b')->table('appointments_b')->insert([
                'appt_id' => (string) Str::uuid(),
                'p_id' => $patientId,
                'scheduled_on' => now()->addDays(rand(1, 14))->setTime(rand(8, 17), 0),
                'status_code' => 'pending',
                'service_type' => 'consultation',
                'practitioner_id' => rand(1, 5),
                'additional_info' => 'Pending appointment',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Una cita confirmada
            DB::connection('mysql_center_b')->table('appointments_b')->insert([
                'appt_id' => (string) Str::uuid(),
                'p_id' => $patientId,
                'scheduled_on' => now()->addDays(rand(15, 30))->setTime(rand(8, 17), 0),
                'status_code' => 'confirmed',
                'service_type' => 'follow-up',
                'practitioner_id' => rand(1, 5),
                'additional_info' => 'Confirmed appointment',
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ]);
        }
    }
}
