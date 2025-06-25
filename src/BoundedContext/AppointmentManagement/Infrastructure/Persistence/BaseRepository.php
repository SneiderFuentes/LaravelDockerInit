<?php

namespace Core\BoundedContext\AppointmentManagement\Infrastructure\Persistence;

abstract class BaseRepository
{
    protected $configService;
    protected array $configCache = [];

    private const CENTER_KEY = 'datosipsndx';
    public function __construct($configService)
    {
        $this->configService = $configService;
    }

    protected function getConfig(?string $centerKey = self::CENTER_KEY)
    {
        if (!isset($this->configCache[$centerKey])) {
            $this->configCache[$centerKey] = $this->configService->execute($centerKey);
        }
        return $this->configCache[$centerKey];
    }

    public function clearConfigCache(?string $centerKey = null): void
    {
        if ($centerKey) {
            unset($this->configCache[$centerKey]);
        } else {
            $this->configCache = [];
        }
    }
}
