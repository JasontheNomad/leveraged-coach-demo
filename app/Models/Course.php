<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'price_type',
        'published',
    ];

    protected $casts = [
        'published' => 'boolean',
    ];

    /** Sanitized HTML of the raw description, safe for {!! !!} output. */
    public function getDescriptionHtmlAttribute(): string
    {
        return RichText::sanitize($this->description);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }

        if (str_starts_with($this->thumbnail, 'http')) {
            return $this->thumbnail;
        }

        return Storage::disk('r2')->url($this->thumbnail);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
