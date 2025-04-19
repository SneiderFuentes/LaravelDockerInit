<?php

declare(strict_types=1);

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class SubaccountModel extends Model
{
    use HasUuids;

    protected $table = 'subaccounts';

    protected $fillable = [
        'id',
        'key',
        'name',
        'connection',
        'tables',
    ];

    protected $casts = [
        'tables' => 'json',
    ];
}
