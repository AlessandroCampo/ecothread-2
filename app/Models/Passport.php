<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passport extends Model
{
    protected $fillable = [
        'passport_number',
        'product_id',
        'status',
        'verification_result',
        'verified_at',
        'expires_at',
        'requested_by_wallet',
        'verified_by',
        'rejection_reason',
    ];

    protected $casts = [
        'verification_result' => 'array',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Relazione con il prodotto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Genera un nuovo numero di passaporto
     */
    public static function generatePassportNumber(): string
    {
        $year = date('Y');
        $lastPassport = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = $lastPassport 
            ? intval(substr($lastPassport->passport_number, -5)) + 1 
            : 1;

        return sprintf('ECO-%s-%05d', $year, $sequence);
    }

    /**
     * Verifica se il passaporto è valido
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_VERIFIED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * URL pubblica di verifica
     */
    public function getVerificationUrl(): string
    {
        return url("/verify/{$this->passport_number}");
    }

    /**
     * Scope per passaporti validi
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}