<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed de subaccounts
        // $this->call([
        //     \Database\Seeders\CentersTestDataSeeder::class,
        // ]);

        // Comando personalizado para cargar configuración
        $this->command->info('Executing subaccounts:seed command...');
        Artisan::call('subaccounts:seed', [], $this->command->getOutput());
    }
}
