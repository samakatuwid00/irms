<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportedLrhubController extends Controller
{
    // Show upload form
    public function showUploadForm()
    {
        return view('indexImport');
    }

    /**
     * Handle CSV import - Multiple files support
     * Supports up to 100 CSV files at once
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_files' => 'required|array|max:100',
            'csv_files.*' => 'required|mimes:csv,txt|max:10240', // Max 10MB per file
        ]);

        $files = $request->file('csv_files');

        $totalRows = [];
        $successCount = 0;
        $errorCount = 0;
        $fileDetails = [];
        $errors = [];

        set_time_limit(300); // 5 minutes timeout for processing

        foreach ($files as $index => $file) {
            $fileName = $file->getClientOriginalName();
            $fileRows = [];

            try {
                if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                    // Get header row (skip BOM if present)
                    $header = fgetcsv($handle, 1000, ",");

                    // Trim header names (in case CSV has extra spaces)
                    $header = array_map('trim', $header);

                    $rowCount = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                        // Skip empty rows
                        if (count($data) <= 1 && empty($data[0])) {
                            continue;
                        }

                        $row = array_combine($header, $data);

                        // Clean and prepare data
                        $prepared = [
                            'id'                  => (string) Str::uuid(),
                            'title'               => $row['title'] ?? null,
                            'type'                => $row['type'] ?? null,
                            'author'              => $row['author'] ?? null,
                            'publisher'           => $row['publisher'] ?? null,
                            'source'              => $row['source'] ?? null,
                            'status'              => $row['status'] ?? null,
                            'remarks'             => $row['remarks'] ?? null,
                            'short_name'          => $row['short_name'] ?? null,
                            'subject_grade_level' => $row['subject_grade_level'] ?? null,
                            'created_at'          => now(),
                            'updated_at'          => now(),
                        ];

                        // Handle integer fields safely (empty → null)
                        $prepared['copyright_year'] = $this->safeInt($row['copyright_year'] ?? '');
                        $prepared['pages']          = $this->safeInt($row['pages'] ?? '');
                        $prepared['quantity']       = $this->safeInt($row['quantity'] ?? '');
                        $prepared['volume']         = $this->safeInt($row['volume'] ?? '');

                        $fileRows[] = $prepared;
                        $rowCount++;
                    }

                    fclose($handle);

                    if (!empty($fileRows)) {
                        $totalRows = array_merge($totalRows, $fileRows);
                        $fileDetails[] = "{$fileName}: {$rowCount} records";
                    } else {
                        $fileDetails[] = "{$fileName}: No valid records found";
                    }
                } else {
                    throw new \Exception("Failed to open file: {$fileName}");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "{$fileName}: " . $e->getMessage();
                Log::error("CSV Import failed for {$fileName}: " . $e->getMessage());
            }
        }

        // Insert all rows in batches to avoid memory issues
        if (!empty($totalRows)) {
            try {
                $chunks = array_chunk($totalRows, 500); // Insert 500 records at a time
                foreach ($chunks as $chunk) {
                    DB::table('imported_schools')->insert($chunk);
                }
                $successCount = count($totalRows);
            } catch (\Exception $e) {
                Log::error('Bulk insert failed: ' . $e->getMessage());
                return back()->with('error', 'Database insert failed: ' . $e->getMessage());
            }
        }

        // Prepare response
        if ($successCount > 0) {
            $message = "Successfully imported {$successCount} total records from " . count($files) . " file(s).";

            if ($errorCount > 0) {
                $message .= " {$errorCount} file(s) had errors.";
            }

            return back()
                ->with('success', $message)
                ->with('details', $fileDetails)
                ->withErrors($errors);
        }

        if ($errorCount > 0) {
            return back()->with('error', 'All files failed to import.')->withErrors($errors);
        }

        return back()->with('error', 'No valid records found in any CSV file.');
    }

    /**
     * ALTERNATIVE METHOD: Queue-based import for production use
     * Uncomment and use this for better performance with 50+ files
     * Requires: Laravel Queue system configured (database, redis, etc.)
     */
    /*
    public function importCsvQueue(Request $request)
    {
        $request->validate([
            'csv_files' => 'required|array|max:100',
            'csv_files.*' => 'required|mimes:csv,txt|max:10240',
        ]);

        $files = $request->file('csv_files');
        $batchId = Str::uuid();
        $filesQueued = 0;

        foreach ($files as $file) {
            // Store file temporarily
            $filename = $batchId . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('csv_imports', $filename);

            // Dispatch job (requires creating app/Jobs/ImportCsvJob.php)
            \App\Jobs\ImportCsvJob::dispatch($path, $batchId, $file->getClientOriginalName());
            $filesQueued++;
        }

        return back()->with('success',
            "Queued {$filesQueued} file(s) for processing. You'll receive a notification when complete."
        );
    }
    */

    /**
     * Convert value to integer or null
     * - Empty string / whitespace → null
     * - Non-numeric → null
     */
    private function safeInt($value)
    {
        $trimmed = trim($value ?? '');

        if ($trimmed === '') {
            return null;
        }

        // Optional: stricter validation - uncomment if you want to reject invalid numbers
        // if (!is_numeric($trimmed)) {
        //     throw new \Exception("Invalid integer value: '$value'");
        // }

        return (int) $trimmed;
    }
}
