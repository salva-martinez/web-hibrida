<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    use HasFactory;

    protected $fillable = ['estimulo_id', 'nombre', 'video_url', 'descripcion'];

    public function estimulo()
    {
        return $this->belongsTo(Estimulo::class);
    }

    public function planEjercicios()
    {
        return $this->hasMany(PlanEjercicio::class);
    }

    /**
     * Extract YouTube embed URL from various YouTube URL formats.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        if (!$this->video_url) return null;

        $url = $this->video_url;

        // Already an embed URL
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        // youtube.com/watch?v=VIDEO_ID
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        // youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        // youtube.com/shorts/VIDEO_ID
        if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        return $url;
    }
}
