<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class MessageModel extends Model
{
    protected $table = 'communication_messages';

    // Indicar que la clave primaria no es auto-incrementable
    public $incrementing = false;

    // Especificar que la clave primaria es de tipo string (UUID)
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'appointment_id',
        'patient_id',
        'phone_number',
        'content',
        'message_type',
        'status',
        'message_id',
        'message_response',
        'subaccount_key',
        'sent_at',
        'delivered_at',
        'read_at'
    ];

    protected $casts = [
        'id' => 'string',
        'appointment_id' => 'string',
        'patient_id' => 'string',
        'subaccount_key' => 'string',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
