<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementDismissal extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['user_id', 'announcement_id', 'dismissed_at'];

    protected $casts = [
        'dismissed_at' => 'datetime',
    ];
}
