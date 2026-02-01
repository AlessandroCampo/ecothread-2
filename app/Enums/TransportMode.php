<?php

namespace App\Enums;

enum TransportMode: string
{
    case SEA = 'SEA';
    case AIR = 'AIR';
    case ROAD = 'ROAD';
    case RAIL = 'RAIL';
    case MULTIMODAL = 'MULTIMODAL';

    public function label(): string
    {
        return match($this) {
            self::SEA => 'Nave',
            self::AIR => 'Aereo',
            self::ROAD => 'Camion',
            self::RAIL => 'Treno',
            self::MULTIMODAL => 'Combinato',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SEA => 'mdi-ferry',
            self::AIR => 'mdi-airplane',
            self::ROAD => 'mdi-truck',
            self::RAIL => 'mdi-train',
            self::MULTIMODAL => 'mdi-swap-horizontal',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}