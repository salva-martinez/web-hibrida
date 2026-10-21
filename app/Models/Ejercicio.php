<?php

namespace App\Models;

use App\Scopes\FisioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ejercicio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['estimulo_id', 'nombre', 'video_url', 'descripcion', 'fisio_id'];

    /**
     * Hosts de YouTube admitidos.
     */
    public const YOUTUBE_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtu.be',
        'www.youtu.be',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    protected static function booted(): void
    {
        // Cada fisio trabaja con su propio catálogo de ejercicios.
        static::addGlobalScope(new FisioScope);
    }

    public function scopeDeFisio(Builder $query, $fisioId): Builder
    {
        return $query->where('ejercicios.fisio_id', $fisioId);
    }

    public function estimulo()
    {
        // withTrashed(): un estímulo desactivado sigue mostrándose en los planes
        // antiguos donde ya fue pautado.
        return $this->belongsTo(Estimulo::class)->withTrashed();
    }

    public function planEjercicios()
    {
        return $this->hasMany(PlanEjercicio::class);
    }

    /**
     * Extrae el identificador del vídeo de una URL de YouTube.
     *
     * Devuelve null si la URL no es de YouTube, si no usa https o si no apunta
     * a un vídeo con el formato esperado. Es la única puerta de entrada: tanto
     * el iframe como el enlace externo se construyen a partir de aquí, así que
     * nunca se renderiza una URL proporcionada por el usuario.
     */
    public static function youtubeVideoId(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        // Solo https: descarta "javascript:", "data:", "http:" y cualquier otro esquema.
        if (! preg_match('#^https://#i', $url)) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';
        $id = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = explode('/', ltrim($path, '/'))[0] ?? null;
        } elseif (in_array($host, self::YOUTUBE_HOSTS, true)) {
            if (preg_match('#^/embed/([A-Za-z0-9_-]+)#', $path, $m)) {
                $id = $m[1];
            } elseif (preg_match('#^/shorts/([A-Za-z0-9_-]+)#', $path, $m)) {
                $id = $m[1];
            } elseif ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            }
        }

        // El identificador de un vídeo de YouTube tiene siempre 11 caracteres.
        return (is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? $id : null;
    }

    /**
     * URL del embed (para el iframe del modal).
     *
     * Se construye siempre a partir del identificador: la URL original nunca
     * llega al navegador.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $id = static::youtubeVideoId($this->video_url);

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}" : null;
    }

    /**
     * URL canónica del vídeo en YouTube (para el enlace "ver en YouTube").
     */
    public function getYoutubeUrlAttribute(): ?string
    {
        $id = static::youtubeVideoId($this->video_url);

        return $id ? "https://www.youtube.com/watch?v={$id}" : null;
    }

    public function tieneVideo(): bool
    {
        return static::youtubeVideoId($this->video_url) !== null;
    }
}
