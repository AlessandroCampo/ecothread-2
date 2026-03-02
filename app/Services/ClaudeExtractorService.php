<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Enums\Material;
use App\Enums\ProductionProcess;
use App\Enums\TransportMode;
use App\Enums\PackagingMaterial;
use App\Enums\CertificationType;

class ClaudeExtractorService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-sonnet-4-20250514';
    private const VERSION = '2023-06-01';

    /**
     * Estrae i dati da un documento per un event_type specifico.
     * Usa gli enum reali nel prompt e valida l'output con le stesse regole del backend.
     *
     * @return array{extracted: array, confidence: string, notes: string|null, validation_errors: array, usage: array}
     */
    public function extractFromDocument(
        UploadedFile|string $file,
        string $eventType,
        ?string $rawText = null
    ): array {
        $enums  = $this->getEnumsForEvent($eventType);
        $fields = $this->getFieldsForEvent($eventType);

        $content = $rawText !== null
            ? $this->buildTextContent($rawText, $eventType, $fields, $enums)
            : $this->buildDocumentContent($file, $eventType, $fields, $enums);

        $response         = $this->callApi($content);
        $extracted        = $this->parseResponse($response);
        $validationErrors = $this->validateExtracted($eventType, $extracted['extracted']);

        return array_merge($extracted, ['validation_errors' => $validationErrors]);
    }

    /**
     * Scorciatoia da testo grezzo già disponibile (es. output Textract).
     */
    public function extractFromText(string $rawText, string $eventType): array
    {
        return $this->extractFromDocument('', $eventType, $rawText);
    }

    // -------------------------------------------------------------------------
    // Enums dinamici — stessa fonte di eventEnums() nel controller
    // -------------------------------------------------------------------------

    private function getEnumsForEvent(string $eventType): array
    {
        return match($eventType) {
            'ORIGIN'        => ['materials'          => Material::toArray()],
            'PRODUCTION'    => ['processes'           => ProductionProcess::toArray()],
            'TRANSPORT'     => ['transport_modes'     => TransportMode::toArray()],
            'PACKAGING'     => ['packaging_materials' => PackagingMaterial::toArray()],
            'CERTIFICATION' => ['certification_types' => CertificationType::toArray()],
            default         => [],
        };
    }

    private function getFieldsForEvent(string $eventType): array
    {
        return match($eventType) {
            'CERTIFICATION' => ['certification_type', 'issued_by', 'valid_until', 'certificate_number'],
            'ORIGIN'        => ['compositions', 'country', 'region'],
            'PRODUCTION'    => ['processes', 'water_usage_liters', 'energy_kwh'],
            'TRANSPORT'     => ['origin_country', 'destination_country', 'transport_mode', 'distance_km', 'co2_kg'],
            'PACKAGING'     => ['materials', 'is_recyclable'],
            'RECYCLE'       => ['recycle_percentage', 'take_back_program'],
            default         => [],
        };
    }

    // -------------------------------------------------------------------------
    // Validazione — riusa le stesse regole di validateEventMetadata()
    // -------------------------------------------------------------------------

    private function validateExtracted(string $eventType, array $data): array
    {
        $rules = match($eventType) {
            'ORIGIN' => [
                'country'                      => 'required|string|size:2',
                'region'                       => 'nullable|string|max:100',
                'compositions'                 => 'required|array|min:1',
                'compositions.*.material'      => 'required|string',
                'compositions.*.percentage'    => 'required|numeric|min:0|max:100',
                'compositions.*.certification' => 'nullable|string|max:50',
                'compositions.*.is_recycled'   => 'nullable|boolean',
            ],
            'PRODUCTION' => [
                'processes'          => 'required|array|min:1',
                'processes.*'        => 'string',
                'water_usage_liters' => 'nullable|numeric|min:0',
                'energy_kwh'         => 'nullable|numeric|min:0',
            ],
            'TRANSPORT' => [
                'origin_country'      => 'required|string|size:2',
                'destination_country' => 'required|string|size:2',
                'transport_mode'      => 'required|string',
                'distance_km'         => 'nullable|numeric|min:0',
                'co2_kg'              => 'nullable|numeric|min:0',
            ],
            'PACKAGING' => [
                'materials'     => 'required|array|min:1',
                'materials.*'   => 'string',
                'is_recyclable' => 'required|boolean',
            ],
            'RECYCLE' => [
                'recycle_percentage' => 'required|numeric|min:0|max:100',
                'take_back_program'  => 'required|boolean',
            ],
            'CERTIFICATION' => [
                'certification_type' => 'required|string',
                'issued_by'          => 'required|string|max:255',
                'valid_until'        => 'nullable|date',
                'certificate_number' => 'nullable|string|max:100',
            ],
            default => [],
        };

        if (empty($rules)) {
            return [];
        }

        $validator = Validator::make($data, $rules);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }

    // -------------------------------------------------------------------------
    // Prompt builder
    // -------------------------------------------------------------------------

    private function buildPrompt(string $eventType, array $fields, array $enums): string
    {
        $fieldsStr  = empty($fields)
            ? 'Estrai tutti i dati rilevanti.'
            : 'Estrai esattamente questi campi: ' . implode(', ', $fields) . '.';

        $enumsBlock = $this->buildEnumsBlock($enums);
        $fieldNotes = $this->buildFieldNotes($eventType);

        return <<<PROMPT
Sei un assistente specializzato nell'analisi di certificati e documenti della filiera tessile.

Analizza il documento e restituisci SOLO un oggetto JSON valido (senza markdown, senza backtick).

Struttura richiesta:
{
  "extracted": { /* campi estratti */ },
  "confidence": "high|medium|low",
  "notes": "eventuali avvertenze"
}

Event type: {$eventType}
{$fieldsStr}

{$fieldNotes}

{$enumsBlock}

Regole:
- Campi assenti nel documento → null
- Date in formato YYYY-MM-DD
- Numeri come numeri, non stringhe
- is_recyclable e take_back_program → true/false
- country, origin_country, destination_country → codice ISO 3166-1 alpha-2 (es. IT, DE, FR)
- confidence: "high" se tutti i campi principali sono chiari, "medium" se alcuni incerti, "low" se documento poco leggibile o non pertinente
- Rispondi SOLO con il JSON, nessun testo prima o dopo
PROMPT;
    }

    /**
     * Costruisce il blocco enum dal risultato di EnumClass::toArray().
     * Adatta il pluck('value') alla struttura reale dei tuoi enum.
     */
    private function buildEnumsBlock(array $enums): string
    {
        if (empty($enums)) {
            return '';
        }

        $lines = ["Valori validi per i campi enum (usa SOLO questi):"];

        foreach ($enums as $enumName => $values) {
            $validValues = collect($values)
                ->pluck('value')   // {label: '...', value: '...'}
                ->filter()
                ->implode(', ');

            $lines[] = "- {$enumName}: [{$validValues}]";
        }

        return implode("\n", $lines);
    }

    private function buildFieldNotes(string $eventType): string
    {
        return match($eventType) {
            'ORIGIN' => <<<NOTES
Note campi:
- compositions: array di oggetti {material: "valore_enum", percentage: numero} — devono sommare 100
- country: paese di origine delle materie prime (ISO alpha-2)
NOTES,
            'PRODUCTION' => <<<NOTES
Note campi:
- processes: array di valori enum delle lavorazioni trovate nel documento
- water_usage_liters: litri per capo prodotto se indicato
- energy_kwh: kWh per capo prodotto se indicato
NOTES,
            'TRANSPORT' => <<<NOTES
Note campi:
- origin_country / destination_country: codici ISO alpha-2
- transport_mode: usa il valore enum più vicino al mezzo indicato nel documento
- co2_kg: emissioni totali del trasporto in kg CO2
NOTES,
            'CERTIFICATION' => <<<NOTES
Note campi:
- certification_type: usa il valore enum più vicino al tipo di certificazione trovato
- issued_by: nome esatto dell'ente certificatore come appare nel documento
NOTES,
            default => '',
        };
    }

    // -------------------------------------------------------------------------
    // Build content blocks per API
    // -------------------------------------------------------------------------

    private function buildDocumentContent(
        UploadedFile|string $file,
        string $eventType,
        array $fields,
        array $enums
    ): array {
        [$bytes, $mimeType] = $this->readFile($file);
        $base64 = base64_encode($bytes);

        $documentBlock = $mimeType === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64]]
            : ['type' => 'image',    'source' => ['type' => 'base64', 'media_type' => $mimeType,          'data' => $base64]];

        return [
            $documentBlock,
            ['type' => 'text', 'text' => $this->buildPrompt($eventType, $fields, $enums)],
        ];
    }

    private function buildTextContent(string $rawText, string $eventType, array $fields, array $enums): array
    {
        $prompt = "Testo estratto dal documento:\n\n---\n{$rawText}\n---\n\n"
                . $this->buildPrompt($eventType, $fields, $enums);

        return [['type' => 'text', 'text' => $prompt]];
    }

    // -------------------------------------------------------------------------
    // API call
    // -------------------------------------------------------------------------

    private function callApi(array $content): array
    {
        $response = Http::withHeaders([
            'x-api-key'         => config('services.claude.api_key'),
            'anthropic-version' => self::VERSION,
            'content-type'      => 'application/json',
        ])
        ->withoutVerifying()
        ->timeout(60)->post(self::API_URL, [
            'model'      => self::MODEL,
            'max_tokens' => 1024,
            'messages'   => [['role' => 'user', 'content' => $content]],
        ]);

        if ($response->failed()) {
            Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Errore Claude API: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // Parse response
    // -------------------------------------------------------------------------

    private function parseResponse(array $apiResponse): array
    {
        $text = $apiResponse['content'][0]['text'] ?? '';
        $text = trim(preg_replace('/```json|```/', '', $text));

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Claude response non è JSON valido', ['raw' => $text]);
            throw new \RuntimeException('Claude ha restituito un output non valido: ' . $text);
        }

        return [
            'extracted'  => $decoded['extracted'] ?? [],
            'confidence' => $decoded['confidence'] ?? 'low',
            'notes'      => $decoded['notes'] ?? null,
            'usage'      => $apiResponse['usage'] ?? [],
        ];
    }

    // -------------------------------------------------------------------------
    // File helpers
    // -------------------------------------------------------------------------

    private function readFile(UploadedFile|string $file): array
    {
        if ($file instanceof UploadedFile) {
            $bytes    = file_get_contents($file->getRealPath());
            $mimeType = $file->getMimeType();
        } else {
            $bytes    = file_get_contents($file);
            $mimeType = mime_content_type($file);
        }

        if ($bytes === false) {
            throw new \RuntimeException('Impossibile leggere il file.');
        }

        $mimeType = match(true) {
            str_contains($mimeType, 'pdf')  => 'application/pdf',
            str_contains($mimeType, 'jpeg'),
            str_contains($mimeType, 'jpg')  => 'image/jpeg',
            str_contains($mimeType, 'png')  => 'image/png',
            str_contains($mimeType, 'webp') => 'image/webp',
            default => $mimeType,
        };

        return [$bytes, $mimeType];
    }

    /**
 * Estrae dati passando direttamente i bytes del file.
 */
public function extractFromBytes(
    string $bytes,
    string $mimeType,
    string $eventType
): array {
    $enums  = $this->getEnumsForEvent($eventType);
    $fields = $this->getFieldsForEvent($eventType);

    $content  = $this->buildDocumentContentFromBytes($bytes, $mimeType, $eventType, $fields, $enums);
    $response = $this->callApi($content);
    $extracted = $this->parseResponse($response);

    return array_merge($extracted, [
        'validation_errors' => $this->validateExtracted($eventType, $extracted['extracted'])
    ]);
}

private function buildDocumentContentFromBytes(
    string $bytes,
    string $mimeType,
    string $eventType,
    array $fields,
    array $enums
): array {
    $base64 = base64_encode($bytes);

    $documentBlock = $mimeType === 'application/pdf'
        ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64]]
        : ['type' => 'image',    'source' => ['type' => 'base64', 'media_type' => $mimeType,          'data' => $base64]];

    return [
        $documentBlock,
        ['type' => 'text', 'text' => $this->buildPrompt($eventType, $fields, $enums)],
    ];
}
}