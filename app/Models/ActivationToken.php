<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public static function generate(Member $member, int $expiresInHours = 48): self
    {
        return self::create([
            'member_id' => $member->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
