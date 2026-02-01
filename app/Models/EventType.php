<?php

namespace App\Models;

use App\Enums\EventType as EventTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'code',
        'label',
        'icon',
        'description',
        'is_required',
        'sort_order',
    ];
    
    protected $casts = [
        'code' => EventTypeEnum::class,
        'is_required' => 'boolean',
    ];
    
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'event_type', 'code');
    }
    
    // Scopes
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
    
    /**
     * Ottieni metadati da enum (utile per sincronizzazione)
     */
    public static function fromEnum(EventTypeEnum $enum): ?self
    {
        return self::find($enum->value);
    }
}