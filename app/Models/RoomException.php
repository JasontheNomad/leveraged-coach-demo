<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomException extends Model
{
    protected $fillable = [
        'room_id',
        'date',
        'status',
        'moved_to',
    ];

    protected $casts = [
        'date'     => 'date',
        'moved_to' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
