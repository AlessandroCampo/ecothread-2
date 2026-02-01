<?php

namespace App\Enums;

enum ProductType: string
{
    // Abbigliamento
    case TSHIRT = 'TSHIRT';
    case SHIRT = 'SHIRT';
    case PANTS = 'PANTS';
    case JACKET = 'JACKET';
    case DRESS = 'DRESS';
    case SWEATER = 'SWEATER';
    
    // Accessori
    case BAG = 'BAG';
    case SHOES = 'SHOES';
    case SCARF = 'SCARF';
    
    // Tessile casa
    case BEDDING = 'BEDDING';
    case TOWEL = 'TOWEL';
    
    // Generico
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match($this) {
            self::TSHIRT => 'T-Shirt',
            self::SHIRT => 'Camicia',
            self::PANTS => 'Pantaloni',
            self::JACKET => 'Giacca',
            self::DRESS => 'Abito',
            self::SWEATER => 'Maglione',
            self::BAG => 'Borsa',
            self::SHOES => 'Scarpe',
            self::SCARF => 'Sciarpa',
            self::BEDDING => 'Biancheria letto',
            self::TOWEL => 'Asciugamano',
            self::OTHER => 'Altro',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::TSHIRT, self::SHIRT, self::SWEATER => '👕',
            self::PANTS => '👖',
            self::JACKET => '🧥',
            self::DRESS => '👗',
            self::BAG => '👜',
            self::SHOES => '👟',
            self::SCARF => '🧣',
            self::BEDDING => '🛏️',
            self::TOWEL => '🛁',
            self::OTHER => '📦',
        };
    }

    public function category(): string
    {
        return match($this) {
            self::TSHIRT, self::SHIRT, self::PANTS, 
            self::JACKET, self::DRESS, self::SWEATER => 'clothing',
            self::BAG, self::SHOES, self::SCARF => 'accessories',
            self::BEDDING, self::TOWEL => 'home',
            self::OTHER => 'other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    public static function grouped(): array
    {
        return [
            'Abbigliamento' => [
                self::TSHIRT, self::SHIRT, self::PANTS,
                self::JACKET, self::DRESS, self::SWEATER,
            ],
            'Accessori' => [
                self::BAG, self::SHOES, self::SCARF,
            ],
            'Tessile Casa' => [
                self::BEDDING, self::TOWEL,
            ],
            'Altro' => [
                self::OTHER,
            ],
        ];
    }

     public static function toArray(): array
    {
        $types = array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
            'category' => $case->category(),
           
        ], self::cases());

        return $types;
    }
}