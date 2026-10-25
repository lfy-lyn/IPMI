<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    /** Kunci yang tidak boleh pernah tersimpan di audit log (D-017). */
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'token', 'remember_token', '_token'];

    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'ip_address',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Catat tindakan penting. Contoh:
     * AuditLog::record('member.class_corrected', $member, 'Koreksi kelas', ['alasan' => '...']);
     */
    public static function record(string $action, ?Model $subject = null, ?string $description = null, array $metadata = []): self
    {
        return static::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => static::sanitize($metadata) ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    private static function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = static::sanitize($value);
            }
        }

        return $data;
    }
}
