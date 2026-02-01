<?php

namespace App\Enums;

enum CertificationType: string
{
    case GOTS = 'GOTS';
    case OEKO_TEX = 'OEKO_TEX';
    case GRS = 'GRS';
    case OCS = 'OCS';
    case BSCI = 'BSCI';
    case FAIR_TRADE = 'FAIR_TRADE';
    case BLUESIGN = 'BLUESIGN';
    case ISO_14001 = 'ISO_14001';
    case ISO_9001 = 'ISO_9001';
    case SA8000 = 'SA8000';
    case BCORP = 'BCORP';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match($this) {
            self::GOTS => 'GOTS (Global Organic Textile Standard)',
            self::OEKO_TEX => 'OEKO-TEX Standard 100',
            self::GRS => 'GRS (Global Recycled Standard)',
            self::OCS => 'OCS (Organic Content Standard)',
            self::BSCI => 'BSCI (Business Social Compliance)',
            self::FAIR_TRADE => 'Fair Trade',
            self::BLUESIGN => 'Bluesign',
            self::ISO_14001 => 'ISO 14001 (Ambiente)',
            self::ISO_9001 => 'ISO 9001 (Qualità)',
            self::SA8000 => 'SA8000 (Social Accountability)',
            self::BCORP => 'B Corp',
            self::OTHER => 'Altra certificazione',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::GOTS => 'Certifica fibre biologiche e processi sostenibili',
            self::OEKO_TEX => 'Certifica assenza di sostanze nocive',
            self::GRS => 'Certifica contenuto riciclato',
            self::OCS => 'Certifica contenuto organico',
            self::BSCI => 'Certifica condizioni di lavoro etiche',
            self::FAIR_TRADE => 'Certifica commercio equo e solidale',
            self::BLUESIGN => 'Certifica sostenibilità chimica',
            self::ISO_14001 => 'Sistema di gestione ambientale',
            self::ISO_9001 => 'Sistema di gestione qualità',
            self::SA8000 => 'Standard internazionale responsabilità sociale',
            self::BCORP => 'Certificazione impatto sociale e ambientale',
            self::OTHER => 'Certificazione non in elenco',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
        ], self::cases());
    }
}