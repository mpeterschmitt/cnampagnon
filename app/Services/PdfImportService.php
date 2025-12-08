<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class PdfImportService
{
    /**
     * Process a PDF file and extract events data
     *
     * @param  string  $filePath  Full path to the PDF file
     * @return array The extracted events data
     *
     * @throws \Exception If processing fails
     */
    public function processFile(string $filePath): array
    {
        $scriptPath = base_path('main.py');

        if (! file_exists($scriptPath)) {
            throw new \Exception('Python script not found at: '.$scriptPath);
        }

        if (! file_exists($filePath)) {
            throw new \Exception('PDF file not found at: '.$filePath);
        }

        $outputFilePath = $filePath . '.ics';


        try {

            $result = Process::run(['python', $scriptPath, '--output', $outputFilePath, $filePath]);

            if ($result->failed()) {
                throw new \Exception('Python Error: '.$result->errorOutput());
            }

            if (! file_exists($outputFilePath)) {
                throw new \Exception('Python script did not generate output file at: '.$outputFilePath);
            }

            $icsService = new IcsImportService();
            $data = $icsService->parseIcsFile($outputFilePath);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse JSON output: '.json_last_error_msg());
            }

            return $data;
        } finally {
            // Clean up the temporary input file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
