<?php

namespace App\Enums;

enum PackagingMaterial: string
{
    case CARDBOARD = 'CARDBOARD';
    case PAPER = 'PAPER';
    case RECYCLED_PAPER = 'RECYCLED_PAPER';
    case PLASTIC = 'PLASTIC';
    case RECYCLED_PLASTIC = 'RECYCLED_PLASTIC';
    case FABRIC = 'FABRIC';
    case COMPOSTABLE = 'COMPOSTABLE';
    case NONE = 'NONE';

    public function label(): string
    {
        return match($this) {
            self::CARDBOARD => 'Cartone',
            self::PAPER => 'Carta',
            self::RECYCLED_PAPER => 'Carta riciclata',
            self::PLASTIC => 'Plastica',
            self::RECYCLED_PLASTIC => 'Plastica riciclata',
            self::FABRIC => 'Tessuto',
            self::COMPOSTABLE => 'Materiale compostabile',
            self::NONE => 'Nessun packaging',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}