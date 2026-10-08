<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'video_url',
        'body',
        'duration_seconds',
        'position',
        'is_preview',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
    ];

    /** Sanitized HTML of the raw body, safe for {!! !!} output. */
    public function getBodyHtmlAttribute(): string
    {
        return RichText::sanitize($this->body);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        $url = $this->video_url;
        if (!$url) return null;

        // YouTube: watch?v=ID or youtu.be/ID
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // Vimeo: vimeo.com/ID
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        // Bunny Stream: player.mediadelivery.net/play/LIBRARY/GUID
        if (preg_match('/mediadelivery\.net\/play\/(\d+)\/([a-f0-9-]+)/', $url, $m)) {
            return 'https://iframe.mediadelivery.net/embed/' . $m[1] . '/' . $m[2];
        }

        // Direct file — return as-is
        return $url;
    }

    public function getVideoTypeAttribute(): string
    {
        $url = $this->video_url;
        if (!$url) return 'none';
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url)) return 'youtube';
        if (preg_match('/vimeo\.com\/(\d+)/', $url)) return 'vimeo';
        if (preg_match('/mediadelivery\.net/', $url)) return 'bunny';
        return 'file';
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
