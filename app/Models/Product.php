<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\CollectionSeason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

       protected $fillable = [
        'id',
        'name',
        'description',
        'url',
        'product_type',
        'collection_year',
        'image_path',
        'creator_wallet',
        'creation_timestamp',
        'pda_address',
        'tx_signature',
        'status',
        'is_on_chain',
    ];


    protected $appends = ['image_url'];


    protected $casts = [
        'product_type' => ProductType::class,
        'collection_year' => 'integer',
        'creation_timestamp' => 'integer',
    ];

        public function getImageUrlAttribute()
    {
        return $this->image_path 
            ? Storage::url($this->image_path) 
            : 'https://via.placeholder.com/300x400?text=No+Image';
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'product_id', 'id');
    }

    public function company()
        {
            return $this->belongsTo(User::class, 'creator_wallet', 'wallet_address');
        }


    public function passport()
    {
        return $this->hasOne(Passport::class);
    }

    /**
     * Formato leggibile della collezione: "P/E 2025"
     */
    public function getCollectionLabelAttribute(): string
    {
        $season = $this->collection_season?->shortLabel() ?? '';
        return trim("{$season} {$this->collection_year}");
    }
}