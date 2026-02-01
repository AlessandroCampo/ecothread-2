<?php

namespace App\Enums;

enum ProductionProcess: string
{
    case SPINNING = 'SPINNING';
    case WEAVING = 'WEAVING';
    case KNITTING = 'KNITTING';
    case DYEING = 'DYEING';
    case PRINTING = 'PRINTING';
    case WASHING = 'WASHING';
    case CUTTING = 'CUTTING';
    case SEWING = 'SEWING';
    case FINISHING = 'FINISHING';
    case ASSEMBLY = 'ASSEMBLY';
    case EMBROIDERY = 'EMBROIDERY';
    case COATING = 'COATING';

    public function label(): string
    {
        return match($this) {
            self::SPINNING => 'Filatura',
            self::WEAVING => 'Tessitura',
            self::KNITTING => 'Maglieria',
            self::DYEING => 'Tintura',
            self::PRINTING => 'Stampa',
            self::WASHING => 'Lavaggio',
            self::CUTTING => 'Taglio',
            self::SEWING => 'Cucitura',
            self::FINISHING => 'Finissaggio',
            self::ASSEMBLY => 'Assemblaggio',
            self::EMBROIDERY => 'Ricamo',
            self::COATING => 'Rivestimento',
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