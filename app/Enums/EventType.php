<?php

namespace App\Enums;

enum EventType: string
{
    // Obbligatori (supply chain)
    case ORIGIN = 'ORIGIN';
    case PRODUCTION = 'PRODUCTION';
    case TRANSPORT = 'TRANSPORT';
    case ENV_CLAIM = 'ENV_CLAIM';
    
    // Opzionali
    case CERTIFICATION = 'CERTIFICATION';
    case PACKAGING = 'PACKAGING';
    case RECYCLE = 'RECYCLE';
    case CUSTOM = 'CUSTOM';

    public function label(): string
    {
        return match($this) {
            self::ORIGIN => 'Origine materie prime',
            self::PRODUCTION => 'Produzione',
            self::TRANSPORT => 'Trasporto',
            self::ENV_CLAIM => 'Dichiarazione ambientale',
            self::CERTIFICATION => 'Certificazione',
            self::PACKAGING => 'Packaging',
            self::RECYCLE => 'Riciclo',
            self::CUSTOM => 'Personalizzato'
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ORIGIN => 'mdi-leaf',
            self::PRODUCTION => 'mdi-factory',
            self::TRANSPORT => 'mdi-truck',
            self::ENV_CLAIM => 'mdi-earth',
            self::CERTIFICATION => 'mdi-certificate',
            self::PACKAGING => 'mdi-package-variant',
            self::RECYCLE => 'mdi-recycle',
            self::CUSTOM => 'mdi-tag-outline'  // ✅ Aggiunto
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ORIGIN => 'green',
            self::PRODUCTION => 'blue',
            self::TRANSPORT => 'orange',
            self::ENV_CLAIM => 'teal',
            self::CERTIFICATION => 'amber',
            self::PACKAGING => 'brown',
            self::RECYCLE => 'lime',      // ✅ Aggiunto
            self::CUSTOM => 'deep-purple' // ✅ Aggiunto
        };
    }

    public function description(): string
    {
        return match($this) {
            self::ORIGIN => 'Provenienza, composizione e certificazioni delle materie prime',
            self::PRODUCTION => 'Processo produttivo, luogo di fabbricazione e metriche ambientali',
            self::TRANSPORT => 'Logistica, modalità di trasporto e impatto ambientale',
            self::ENV_CLAIM => 'Claim finale del brand sulla sostenibilità del prodotto',
            self::CERTIFICATION => 'Certificazioni ottenute (GOTS, OEKO-TEX, etc.)',
            self::PACKAGING => 'Materiali e sostenibilità del confezionamento',
            self::RECYCLE => 'Processo di riciclo e fine vita del prodotto',        // ✅ Aggiunto
            self::CUSTOM => 'Eventi personalizzati del brand'                         // ✅ Aggiunto
        };
    }

    public function sortOrder(): int
    {
        return match($this) {
            self::ORIGIN => 1,
            self::PRODUCTION => 2,
            self::TRANSPORT => 3,
            self::PACKAGING => 4,
            self::RECYCLE => 5,           // ✅ Aggiunto
            self::CERTIFICATION => 11,
            self::CUSTOM => 20,           // ✅ Aggiunto
            self::ENV_CLAIM => 99,        // Sempre ultimo
        };
    }

    /**
     * Tipi obbligatori per un prodotto completo
     */
    public static function required(): array
    {
        return [
            self::ORIGIN,
            self::PRODUCTION,
            self::TRANSPORT,
            self::ENV_CLAIM,
        ];
    }
    
    /**
     * Verifica se questo tipo è obbligatorio
     */
    public function isRequired(): bool
    {
        return in_array($this, self::required());
    }
    
    /**
     * Tutti i valori come array di stringhe (utile per validation rules)
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Per il frontend - ritorna array ordinato
     */
    public static function toArray(): array
    {
        $types = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
            'color' => $case->color(),
            'description' => $case->description(),
            'is_required' => $case->isRequired(),
            'sort_order' => $case->sortOrder(),
        ], self::cases());

        // Ordina per sort_order
        usort($types, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $types;
    }
}
