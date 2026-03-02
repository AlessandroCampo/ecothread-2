<?php

namespace App\Console\Commands;

use App\Services\ClaudeExtractorService;
use App\Services\TextractService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

       
        $bytes = Storage::disk('local')->get('certificates/test.pdf');
        $claude = app(ClaudeExtractorService::class);
        $result = $claude->extractFromBytes($bytes, 'application/pdf', 'CERTIFICATION');

        dd($result);

        
        $textract = app(TextractService::class);
        $result = $textract->analyzeDocument(public_path('certificates/test.pdf'));
        $keyValues = $result['key_values'];
        dd($keyValues);
    }
}
