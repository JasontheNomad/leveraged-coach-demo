<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Recording;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GroupService
{
    /**
     * Scope a query so it only returns content accessible to the user:
     *   - No content_groups row exists for this item  → public (everyone can see it)
     *   - A content_groups row exists for one of the user's groups → user has access
     */
    public function scopeAccessible(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        $type         = $this->contentType($query->getModel());
        $table        = $query->getModel()->getTable();
        $userGroupIds = $user->groups()->allRelatedIds();

        return $query->where(function (Builder $q) use ($type, $table, $userGroupIds, $user) {
            // Public: no assignments in content_groups at all
            $q->whereNotExists(function ($sub) use ($type, $table) {
                $sub->select(DB::raw(1))
                    ->from('content_groups')
                    ->whereColumn('content_groups.content_id', "{$table}.id")
                    ->where('content_groups.content_type', $type);
            })
            // OR: assigned to at least one group the user belongs to
            ->orWhereExists(function ($sub) use ($type, $table, $userGroupIds) {
                $sub->select(DB::raw(1))
                    ->from('content_groups')
                    ->whereColumn('content_groups.content_id', "{$table}.id")
                    ->where('content_groups.content_type', $type)
                    ->whereIn('content_groups.group_id', $userGroupIds);
            });

            // OR: directly assigned to this user (courses only)
            if ($type === 'course') {
                $q->orWhereExists(function ($sub) use ($table, $user) {
                    $sub->select(DB::raw(1))
                        ->from('course_user')
                        ->whereColumn('course_user.course_id', "{$table}.id")
                        ->where('course_user.user_id', $user->id);
                });
            }
        });
    }

    /**
     * Can this user access a specific piece of content?
     */
    public function canAccessContent(User $user, string $type, int $id): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Direct individual access (courses only)
        if ($type === 'course') {
            if (DB::table('course_user')->where('course_id', $id)->where('user_id', $user->id)->exists()) {
                return true;
            }
        }

        $hasAssignment = DB::table('content_groups')
            ->where('content_type', $type)
            ->where('content_id', $id)
            ->exists();

        if (! $hasAssignment) {
            return true; // Not gated — public
        }

        $userGroupIds = $user->groups()->allRelatedIds();

        return DB::table('content_groups')
            ->where('content_type', $type)
            ->where('content_id', $id)
            ->whereIn('group_id', $userGroupIds)
            ->exists();
    }

    /**
     * Sync content_groups rows for a piece of content.
     */
    public function syncContentGroups(string $type, int $contentId, array $groupIds): void
    {
        DB::table('content_groups')
            ->where('content_type', $type)
            ->where('content_id', $contentId)
            ->delete();

        $now = now();
        foreach ($groupIds as $groupId) {
            DB::table('content_groups')->insertOrIgnore([
                'group_id'     => $groupId,
                'content_type' => $type,
                'content_id'   => $contentId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    /**
     * Copy all content_groups rows from one content item to another.
     * Used when a Recording inherits group access from its parent Room.
     */
    public function inheritGroups(string $sourceType, int $sourceId, string $targetType, int $targetId): void
    {
        $groups = DB::table('content_groups')
            ->where('content_type', $sourceType)
            ->where('content_id', $sourceId)
            ->pluck('group_id');

        $this->syncContentGroups($targetType, $targetId, $groups->toArray());
    }

    // ── Internal ───────────────────────────────────────────────

    private function contentType(mixed $model): string
    {
        return match (get_class($model)) {
            Course::class    => 'course',
            Room::class      => 'room',
            Recording::class => 'recording',
            default          => throw new \InvalidArgumentException('Unknown content model: ' . get_class($model)),
        };
    }
}
