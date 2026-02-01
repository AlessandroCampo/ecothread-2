<?php

namespace App\Models;

use App\Services\PassportVerificationService;
use App\Services\PinataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
   
    protected $fillable = [
        'product_id',
        'index',
        'event_type',
        'trust_level',
        'description',
        'document_name',
        'document_path',
        'document_hash',
        'document_uri',
        'registrant_wallet',   
        'timestamp',
        'pda_address',
        'tx_signature',
        'status',             
        'is_on_chain',        
        'document_mime_type',
        'metadata'
    ];


    protected $casts = [
        'index' => 'integer',
        'timestamp' => 'integer',
           'metadata' => 'array',
    ];

    protected $appends = ['document_gateway_url'];


    /**
     * Get the product this event belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function getDocumentGatewayUrlAttribute(): ?string
            {
                if (!$this->document_uri) {
                    return null;
                }

                $ps = new PinataService();
                $pvs = new PassportVerificationService($ps);
                
                $url =  $pvs->ipfsToGateway($this->document_uri);

                return $url;
            }

    /**
     * Get Solana Explorer URL for this event's transaction.
     */
    public function getExplorerUrlAttribute(): ?string
    {
        if (!$this->tx_signature) {
            return null;
        }
        return "https://explorer.solana.com/tx/{$this->tx_signature}?cluster=devnet";
    }

    /**
     * Check if event is confirmed on-chain.
     */
    public function getIsOnChainAttribute(): bool
    {
        return !empty($this->tx_signature) && !empty($this->pda_address);
    }
}