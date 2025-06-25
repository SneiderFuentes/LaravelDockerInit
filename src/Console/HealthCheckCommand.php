<?php

namespace Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class HealthCheckCommand extends Command
{
    protected $signature = 'health-check:run';
    protected $description = 'Verifica el estado de la base de datos, Redis y espacio en disco';

    public function handle()
    {
        $this->info('Iniciando health check...');
        $ok = true;

        // Verificar base de datos principal
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->error('✖ Base de datos principal: ERROR - ' . $e->getMessage());
            Log::error('HealthCheck: Base de datos principal: ' . $e->getMessage());
            $ok = false;
        }

        // Verificar segunda base de datos (mysql_datosipsndx)
        try {
            DB::connection('mysql_datosipsndx')->getPdo();
        } catch (\Exception $e) {
            $this->error('✖ Base de datos datosipsndx: ERROR - ' . $e->getMessage());
            Log::error('HealthCheck: Base de datos datosipsndx: ' . $e->getMessage());
            $ok = false;
        }

        // Verificar Redis
        try {
            Redis::ping();
        } catch (\Exception $e) {
            $this->error('✖ Redis: ERROR - ' . $e->getMessage());
            Log::error('HealthCheck: Redis: ' . $e->getMessage());
            $ok = false;
        }

        // Verificar espacio en disco
        $free = disk_free_space(base_path('.'));
        $total = disk_total_space(base_path('.'));
        $percent = round(($free / $total) * 100, 2);
        if ($percent < 10) {
            $this->error('✖ Espacio en disco: Crítico (' . $percent . '% libre)');
            Log::error('HealthCheck: Espacio en disco crítico: ' . $percent . '% libre');
            $ok = false;
        }

        if ($ok) {
            $this->info('Health check: TODO OK');
            return 0;
        } else {
            $this->error('Health check: Problemas detectados');
            return 1;
        }
    }
}
