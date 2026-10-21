<?php

namespace App\Rules;

use App\Models\Ejercicio;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Solo se admiten URLs de YouTube (o YouTube No-Cookie).
 *
 * La validación "url" de Laravel acepta cualquier esquema con una URL sintácticamente
 * válida, incluidas las que empiezan por "javascript:", que luego se renderizan
 * como href o como src de un iframe. Al quedar el atributo en manos del navegador,
 * una URL así ejecutaría JavaScript en la sesión de quien la pulse.
 *
 * Además de restricting los hosts, se exige que la URL apunte a un vídeo real:
 * un identificador de 11 caracteres de [A-Za-z0-9_-].
 */
class YouTubeUrl implements ValidationRule
{
    /**
     * Hosts admitidos.
     */
    protected const HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtu.be',
        'www.youtu.be',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('El campo :attribute debe ser una URL de YouTube válida.');

            return;
        }

        if (Ejercicio::youtubeVideoId($value) === null) {
            $fail('El campo :attribute debe ser una URL de YouTube válida (watch, youtu.be, embed o shorts).');
        }
    }
}
