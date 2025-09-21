<?php

namespace Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class HealthCheckCommand extends Command
{
    protected $signature = 'health-check:run';
    protected $description = 'Verifica el estado de la base de datos, Redis y espacio en disco';

    private $errors = [];

    public function handle()
    {
        $this->info('Iniciando health check...');
        $ok = true;

        // Verificar base de datos principal
        try {
            DB::connection()->getPdo();
            $this->info('✅ Base de datos principal: OK');
        } catch (\Exception $e) {
            $error = 'Base de datos principal: ERROR - ' . $e->getMessage();
            $this->error('✖ ' . $error);
            Log::error('HealthCheck: ' . $error);
            $this->errors[] = '🔴 **DB Principal**: ' . $e->getMessage();
            $ok = false;
        }

        // Verificar segunda base de datos (mysql_datosipsndx)
        try {
            DB::connection('mysql_datosipsndx')->getPdo();
            $this->info('✅ Base de datos datosipsndx: OK');
        } catch (\Exception $e) {
            $error = 'Base de datos datosipsndx: ERROR - ' . $e->getMessage();
            $this->error('✖ ' . $error);
            Log::error('HealthCheck: ' . $error);
            $this->errors[] = '🔴 **DB Externa**: ' . $e->getMessage();
            $ok = false;
        }

        // Verificar Redis
        try {
            Redis::ping();
            $this->info('✅ Redis: OK');
        } catch (\Exception $e) {
            $error = 'Redis: ERROR - ' . $e->getMessage();
            $this->error('✖ ' . $error);
            Log::error('HealthCheck: ' . $error);
            $this->errors[] = '🔴 **Redis**: ' . $e->getMessage();
            $ok = false;
        }

        // Verificar espacio en disco
        $free = disk_free_space(base_path('.'));
        $total = disk_total_space(base_path('.'));
        $percent = round(($free / $total) * 100, 2);
        if ($percent < 10) {
            $error = 'Espacio en disco: Crítico (' . $percent . '% libre)';
            $this->error('✖ ' . $error);
            Log::error('HealthCheck: ' . $error);
            $this->errors[] = '💾 **Disco**: Crítico (' . $percent . '% libre)';
            $ok = false;
        } else {
            $this->info('✅ Espacio en disco: OK (' . $percent . '% libre)');
        }

        // Enviar notificaciones si hay errores
        if (!$ok && !empty($this->errors)) {
            $this->sendTelegramNotification();
        }

        if ($ok) {
            $this->info('Health check: TODO OK');
            return 0;
        } else {
            $this->error('Health check: Problemas detectados');
            return 1;
        }
    }

    private function sendTelegramNotification()
    {
        $botToken = env('TG_BOT_TOKEN');
        $chatIds = env('TG_CHAT_IDS');

        if (empty($botToken) || empty($chatIds)) {
            $this->warn('⚠️ Variables de Telegram no configuradas, saltando notificación');
            return;
        }

        // Sistema de throttling: solo enviar si han pasado X minutos desde la última alerta
        $throttleMinutes = env('HEALTH_ALERT_THROTTLE_MINUTES', 30); // 30 min por defecto
        $lastAlertFile = storage_path('app/.last_health_alert');
        $currentErrorsHash = md5(implode('|', $this->errors));

        // Verificar si ya enviamos esta misma alerta recientemente
        if (file_exists($lastAlertFile)) {
            $lastAlert = json_decode(file_get_contents($lastAlertFile), true);
            $minutesSinceLastAlert = now()->diffInMinutes($lastAlert['timestamp'] ?? now()->subHours(1));

            // Si es el mismo error y no ha pasado el tiempo de throttle, no enviar
            if ($lastAlert['hash'] === $currentErrorsHash && $minutesSinceLastAlert < $throttleMinutes) {
                $this->info("🔇 Alerta throttled (última hace {$minutesSinceLastAlert} min, throttle: {$throttleMinutes} min)");
                return;
            }
        }

        // Determinar qué variable usar (compatibilidad hacia atrás)
        $chatIdsArray = explode(',', $chatIds);

        $message = "🚨 **ALERTA HealthCheck - my-appointments**\n\n";
        $message .= "Se detectaron los siguientes problemas:\n\n";
        $message .= implode("\n", $this->errors);
        $message .= "\n\n⏰ " . now()->format('Y-m-d H:i:s');

        $sent = false;
        foreach ($chatIdsArray as $chatId) {
            $chatId = trim($chatId);
            if (empty($chatId)) continue;

            try {
                Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);
                $this->info("📱 Notificación enviada a chat: {$chatId}");
                $sent = true;
            } catch (\Exception $e) {
                $this->warn("⚠️ Error enviando notificación a chat {$chatId}: " . $e->getMessage());
            }
        }

        // Guardar timestamp y hash de la última alerta enviada
        if ($sent) {
            file_put_contents($lastAlertFile, json_encode([
                'timestamp' => now(),
                'hash' => $currentErrorsHash,
                'errors' => $this->errors
            ]));
        }
    }
}
