<?php

namespace Core\Shared\Infrastructure\Mapping;

class ArrayMapper
{
    public static function mapToLogicalFields(object $row, array $mapping): array
    {
        $result = [];
        foreach ($mapping as $logical => $physical) {
            $result[$logical] = $row->{$physical} ?? null;
        }
        return $result;
    }
}
