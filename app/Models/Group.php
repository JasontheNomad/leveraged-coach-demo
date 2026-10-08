<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Group extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'revoke_on_cancel',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'revoke_on_cancel' => 'boolean',
    ];

    // ── Member relationships ───────────────────────────────────

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function stripePrices(): HasMany
    {
        return $this->hasMany(GroupStripePrice::class);
    }

    // ── Content helpers (via content_groups pivot) ─────────────

    public function courses(): Builder
    {
        $ids = DB::table('content_groups')
            ->where('group_id', $this->id)
            ->where('content_type', 'course')
            ->pluck('content_id');

        return Course::whereIn('id', $ids);
    }

    public function rooms(): Builder
    {
        $ids = DB::table('content_groups')
            ->where('group_id', $this->id)
            ->where('content_type', 'room')
            ->pluck('content_id');

        return Room::whereIn('id', $ids);
    }

    public function recordings(): Builder
    {
        $ids = DB::table('content_groups')
            ->where('group_id', $this->id)
            ->where('content_type', 'recording')
            ->pluck('content_id');

        return Recording::whereIn('id', $ids);
    }

    // ── Member helper methods ──────────────────────────────────

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function addMember(User $user, User $assignedBy): void
    {
        $this->members()->syncWithoutDetaching([
            $user->id => [
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
            ],
        ]);
    }

    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    // ── Slug auto-generation ───────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }
}
