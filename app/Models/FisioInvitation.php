<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FisioInvitation extends Model
{
    use HasFactory;

    protected $fillable = ['token', 'email', 'invited_by_id', 'expires_at'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function generate(User $invitedBy, ?string $email = null): self
    {
        return static::create([
            'token' => Str::random(48),
            'email' => $email,
            'invited_by_id' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by_id');
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function markUsedBy(User $user): void
    {
        $this->forceFill(['used_at' => now(), 'used_by_id' => $user->id])->save();
    }
}
