<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupStripePrice extends Model
{
    protected $fillable = [
        'group_id',
        'stripe_price',
        'mode',
        'label',
        'amount',
        'interval',
        'is_active',
        'sort',
    ];

    protected $casts = [
        'amount'    => 'integer',
        'is_active' => 'boolean',
        'sort'      => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
