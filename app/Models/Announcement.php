<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'icon',
        'visibility_roles',
    ];

    protected $casts = [
        'visibility_roles' => 'array',
    ];

    // Icon auto-set per type for system announcements
    public static function iconForType(string $type): string
    {
        return match ($type) {
            'recording' => 'heroicon-o-video-camera',
            'course'    => 'heroicon-o-academic-cap',
            'lesson'    => 'heroicon-o-book-open',
            'schedule'  => 'heroicon-o-calendar',
            default     => 'heroicon-o-megaphone',
        };
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(\App\Models\AnnouncementDismissal::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'announcement_group');
    }

    /**
     * Scope to announcements visible to the given user.
     * Role filter: visibility_roles is null (all) OR user's role is in the array.
     * Group filter: no groups targeted (all) OR user belongs to at least one targeted group.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query
            ->where(function ($q) use ($user) {
                $q->whereNull('visibility_roles')
                  ->orWhereJsonContains('visibility_roles', $user->role);
            })
            ->where(function ($q) use ($user) {
                $userGroupIds = $user->groups()->allRelatedIds();
                $q->whereDoesntHave('groups')
                  ->orWhereHas('groups', fn($g) => $g->whereIn('groups.id', $userGroupIds));
            });
    }
}
