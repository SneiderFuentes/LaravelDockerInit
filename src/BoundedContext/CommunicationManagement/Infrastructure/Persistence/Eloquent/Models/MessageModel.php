<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class MessageModel extends Model
{
    protected $table = 'communication_messages';

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
        'sent_at',
        'delivered_at',
        'read_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
