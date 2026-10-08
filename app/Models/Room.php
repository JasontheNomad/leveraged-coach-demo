<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use App\Models\Group;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'title',
        'description',
        'daily_room_name',
        'daily_room_url',
        'scheduled_at',
        'host_id',
        'required_plan',
        'enable_recording',
        'recurrence',
        'recurs_until',
        'scheduled_end_at',
        'thumbnail',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'scheduled_end_at' => 'datetime',
        'enable_recording' => 'boolean',
        'recurs_until'     => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(RoomException::class);
    }

    public function isPast(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isPast();
    }

    public function occurrences(Carbon $from, Carbon $to): Collection
    {
        $results = collect();

        if (! $this->scheduled_at) {
            return $results;
        }

        $exceptions = $this->exceptions->keyBy(fn ($e) => $e->date->toDateString());

        $duration = $this->scheduled_end_at
            ? $this->scheduled_at->diffInSeconds($this->scheduled_end_at)
            : 3600;

        $recurrence = $this->recurrence ?? 'none';

        if ($recurrence === 'none') {
            $start = $this->scheduled_at->copy();
            $end   = $start->copy()->addSeconds($duration);

            $exception = $exceptions->get($start->toDateString());
            if ($exception) {
                if ($exception->status === 'cancelled') {
                    return $results;
                }
                if ($exception->status === 'moved' && $exception->moved_to) {
                    $start = $exception->moved_to->copy();
                    $end   = $start->copy()->addSeconds($duration);
                }
            }

            if ($start->between($from, $to)) {
                $results->push($this->makeOccurrence($start, $end));
            }

            return $results;
        }

        $ceiling = $to;
        if ($this->recurs_until) {
            $seriesEnd = Carbon::instance($this->recurs_until)->endOfDay();
            if ($seriesEnd->lt($to)) {
                $ceiling = $seriesEnd;
            }
        }

        $current = $this->scheduled_at->copy();

        if ($recurrence === 'weekdays' && $current->isWeekend()) {
            while ($current->isWeekend()) {
                $current->addDay();
            }
        }

        while ($current->lte($ceiling)) {
            $occDate   = $current->toDateString();
            $exception = $exceptions->get($occDate);

            if ($exception && $exception->status === 'cancelled') {
                $current = $this->nextOccurrence($current, $recurrence);
                continue;
            }

            $start = $current->copy();

            if ($exception && $exception->status === 'moved' && $exception->moved_to) {
                $start = $exception->moved_to->copy();
            }

            $end = $start->copy()->addSeconds($duration);

            if ($start->between($from, $to)) {
                $results->push($this->makeOccurrence($start, $end));
            }

            $current = $this->nextOccurrence($current, $recurrence);
        }

        return $results;
    }

    private function makeOccurrence(Carbon $start, Carbon $end): object
    {
        return (object) [
            'room_id' => $this->id,
            'title'   => $this->title,
            'start'   => $start,
            'end'     => $end,
            'room'    => $this,
        ];
    }

    private function nextOccurrence(Carbon $current, string $recurrence): Carbon
    {
        $next = $current->copy();

        switch ($recurrence) {
            case 'daily':
                return $next->addDay();
            case 'weekdays':
                $next->addDay();
                while ($next->isWeekend()) {
                    $next->addDay();
                }
                return $next;
            case 'weekly':
                return $next->addWeek();
            case 'biweekly':
                return $next->addWeeks(2);
            default:
                return $next->addDay();
        }
    }
}
