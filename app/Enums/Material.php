<?php

namespace App\Enums;

enum Material: string
{
    case COTTON = 'COTTON';
    case ORGANIC_COTTON = 'ORGANIC_COTTON';
    case POLYESTER = 'POLYESTER';
    case RECYCLED_POLYESTER = 'RECYCLED_POLYESTER';
    case WOOL = 'WOOL';
    case MERINO_WOOL = 'MERINO_WOOL';
    case SILK = 'SILK';
    case LINEN = 'LINEN';
    case HEMP = 'HEMP';
    case VISCOSE = 'VISCOSE';
    case LYOCELL = 'LYOCELL';
    case MODAL = 'MODAL';
    case NYLON = 'NYLON';
    case RECYCLED_NYLON = 'RECYCLED_NYLON';
    case ELASTANE = 'ELASTANE';
    case CASHMERE = 'CASHMERE';
    case BAMBOO = 'BAMBOO';
    case LEATHER = 'LEATHER';
    case VEGAN_LEATHER = 'VEGAN_LEATHER';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match($this) {
            self::COTTON => 'Cotone',
            self::ORGANIC_COTTON => 'Cotone biologico',
            self::POLYESTER => 'Poliestere',
            self::RECYCLED_POLYESTER => 'Poliestere riciclato',
            self::WOOL => 'Lana',
            self::MERINO_WOOL => 'Lana merino',
            self::SILK => 'Seta',
            self::LINEN => 'Lino',
            self::HEMP => 'Canapa',
            self::VISCOSE => 'Viscosa',
            self::LYOCELL => 'Lyocell (Tencel)',
            self::MODAL => 'Modal',
            self::NYLON => 'Nylon',
            self::RECYCLED_NYLON => 'Nylon riciclato',
            self::ELASTANE => 'Elastan (Spandex)',
            self::CASHMERE => 'Cashmere',
            self::BAMBOO => 'Bambù',
            self::LEATHER => 'Pelle',
            self::VEGAN_LEATHER => 'Pelle vegana',
            self::OTHER => 'Altro',
        };
    }

    public function isRecyclable(): bool
    {
        return in_array($this, [
            self::RECYCLED_POLYESTER,
            self::RECYCLED_NYLON,
        ]);
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}