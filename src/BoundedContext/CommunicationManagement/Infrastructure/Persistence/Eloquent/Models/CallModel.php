<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CallModel extends Model
{
    protected $table = 'communication_calls';

    protected $fillable = [
        'id',
        'appointment_id',
        'patient_id',
        'phone_number',
        'status',
        'call_type',
        'call_id',
        'flow_id',
        'start_time',
        'end_time',
        'duration',
        'response_data'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'response_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
