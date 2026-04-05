<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationReservation extends Model
{
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'name',
        'email',
        'phone',
        'company',
        'message',
        'status',
        'source',
        'admin_notes',
        'meeting_link',
    ];
}
