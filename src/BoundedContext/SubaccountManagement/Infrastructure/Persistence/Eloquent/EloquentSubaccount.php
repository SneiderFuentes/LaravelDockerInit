<?php

namespace Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class EloquentSubaccount extends Model
{
    protected $table = 'subaccounts';

    protected $fillable = [
        'key',
        'name',
        'config'
    ];

    protected $casts = [
        'config' => 'array'
    ];
}
