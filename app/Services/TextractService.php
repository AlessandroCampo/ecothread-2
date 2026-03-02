<?php

namespace App\Services;

use Aws\Textract\TextractClient;
use Aws\Exception\AwsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class TextractService
{
    private TextractClient $client;

    public function __construct()
    {
        $this->client = new TextractClient([
            'version'     => 'latest',
            'region'      => config('services.aws.region', 'eu-west-1'),
            'credentials' => [
                'key'    => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
            'http' => [
                'verify' => false],
                ]);
    }

    /**
     * Estrae testo, form (key-value) e tabelle da un documento.
     *
     * @param  UploadedFile|string  $file  Oggetto UploadedFile oppure path assoluto al file
     * @param  array<string>        $features  ['FORMS', 'TABLES', 'SIGNATURES'] — default FORMS
     * @return array{
     *     raw: array,
     *     blocks: array,
     *     key_values: array<string, string>,
     *     raw_text: string,
     * }
     *
     * @throws \RuntimeException
     */

 

    public function analyzeDocument(string $bytes, array $features = ['FORMS']): array
    {
     
        if ($bytes === false) {
            throw new \RuntimeException('Impossibile leggere il file da analizzare.');
        }

        // Limite AWS per chiamata sincrona: 5MB
        if (strlen($bytes) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Il file supera il limite di 5MB per l\'analisi sincrona. Usa l\'API asincrona con S3.');
        }

        try {
            $result = $this->client->analyzeDocument([
                'Document'     => ['Bytes' => $bytes],
                'FeatureTypes' => $features,
            ]);
        } catch (AwsException $e) {
             dd([
                'aws_error'    => $e->getAwsErrorMessage(),
                'aws_code'     => $e->getAwsErrorCode(),
                'status_code'  => $e->getStatusCode(),
                'message'      => $e->getMessage(),
            ]);
            Log::error('Textract analyzeDocument error', [
                'message' => $e->getMessage(),
                'code'    => $e->getStatusCode(),
            ]);

            throw new \RuntimeException('Errore Textract: ' . $e->getAwsErrorMessage());
        }

        $blocks = $result['Blocks'] ?? [];

        return [
            'raw'        => $result->toArray(),
            'blocks'     => $blocks,
            'key_values' => $this->extractKeyValues($blocks),
            'raw_text'   => $this->extractRawText($blocks),
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers privati
    // -------------------------------------------------------------------------

    /**
     * Appiattisce le coppie KEY → VALUE dai blocchi Textract.
     *
     * @param  array  $blocks
     * @return array<string, string>
     */
    private function extractKeyValues(array $blocks): array
    {
        // Indicizza tutti i blocchi per ID per navigare le relazioni
        $blockMap = [];
        foreach ($blocks as $block) {
            $blockMap[$block['Id']] = $block;
        }

        $keyValues = [];

        foreach ($blocks as $block) {
            if ($block['BlockType'] !== 'KEY_VALUE_SET') {
                continue;
            }
            if (! isset($block['EntityTypes']) || ! in_array('KEY', $block['EntityTypes'])) {
                continue;
            }

            $key   = $this->getTextFromRelationships($block, 'CHILD', $blockMap);
            $value = '';

            // Trova il blocco VALUE associato tramite relazione VALUE
            foreach ($block['Relationships'] ?? [] as $rel) {
                if ($rel['Type'] !== 'VALUE') {
                    continue;
                }
                foreach ($rel['Ids'] as $valueId) {
                    if (isset($blockMap[$valueId])) {
                        $value = $this->getTextFromRelationships($blockMap[$valueId], 'CHILD', $blockMap);
                    }
                }
            }

            if ($key !== '') {
                $keyValues[trim($key)] = trim($value);
            }
        }

        return $keyValues;
    }

    /**
     * Concatena tutto il testo dai blocchi LINE in ordine di lettura.
     */
    private function extractRawText(array $blocks): string
    {
        $lines = [];
        foreach ($blocks as $block) {
            if ($block['BlockType'] === 'LINE') {
                $lines[] = $block['Text'] ?? '';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Risolve le relazioni di un blocco e concatena il testo dei WORD figli.
     */
    private function getTextFromRelationships(array $block, string $relType, array $blockMap): string
    {
        $words = [];

        foreach ($block['Relationships'] ?? [] as $rel) {
            if ($rel['Type'] !== $relType) {
                continue;
            }
            foreach ($rel['Ids'] as $childId) {
                if (isset($blockMap[$childId]) && $blockMap[$childId]['BlockType'] === 'WORD') {
                    $words[] = $blockMap[$childId]['Text'] ?? '';
                }
            }
        }

        return implode(' ', $words);
    }
}
