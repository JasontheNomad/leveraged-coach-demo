<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupContent extends Model
{
    protected $table = 'group_content';

    protected $fillable = [
        'group_id',
        'content_type',
        'content_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
