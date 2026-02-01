<?php

namespace App\Enums;

enum TrustLevel: string
{
    case AUTODECLARATION = 'autodeclaration';
    case INTERNAL_DOCUMENT = 'internal_document';
    case THIRD_PARTY = 'third_party';
    case VERIFIABLE_CERTIFICATION = 'verifiable_certification';

    public function label(): string
    {
        return match($this) {
            self::AUTODECLARATION => 'Auto-dichiarazione',
            self::INTERNAL_DOCUMENT => 'Documento Interno',
            self::THIRD_PARTY => 'Terza Parte',
            self::VERIFIABLE_CERTIFICATION => 'Certificazione Verificabile',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::AUTODECLARATION => 'mdi-account-voice',
            self::INTERNAL_DOCUMENT => 'mdi-file-document',
            self::THIRD_PARTY => 'mdi-shield-check',
            self::VERIFIABLE_CERTIFICATION => 'mdi-check-decagram',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AUTODECLARATION => 'grey',
            self::INTERNAL_DOCUMENT => 'blue',
            self::THIRD_PARTY => 'green',
            self::VERIFIABLE_CERTIFICATION => 'amber',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::AUTODECLARATION => 'Dichiarazione del brand senza documentazione',
            self::INTERNAL_DOCUMENT => 'Report o documento prodotto internamente',
            self::THIRD_PARTY => 'Certificazione da ente terzo indipendente',
            self::VERIFIABLE_CERTIFICATION => 'Certificazione verificabile on-chain o tramite registry pubblico',
        };
    }

    /**
     * Per validazione Laravel
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Per il frontend
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
            'color' => $case->color(),
            'description' => $case->description(),
        ], self::cases());
    }
}