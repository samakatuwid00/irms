<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OPTIONAL JOB CLASS
 * Only needed if you want to use the queue-based import method
 *
 * To use:
 * 1. Place this file in: app/Jobs/ImportCsvJob.php
 * 2. Configure Laravel queues (config/queue.php)
 * 3. Uncomment the importCsvQueue() method in ImportedLrhubController
 * 4. Update your route to use importCsvQueue instead of importCsv
 * 5. Run: php artisan queue:work
 */
class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes per file
    public $tries = 3; // Retry failed jobs 3 times

    protected $filePath;
    protected $batchId;
    protected $originalName;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $batchId, $originalName)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->originalName = $originalName;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $rows = [];
        $successCount = 0;

        try {
            $fullPath = Storage::path($this->filePath);

            if (($handle = fopen($fullPath, 'r')) !== false) {
                // Get header row
                $header = fgetcsv($handle, 1000, ",");
                $header = array_map('trim', $header);

                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    // Skip empty rows
                    if (count($data) <= 1 && empty($data[0])) {
                        continue;
                    }

                    $row = array_combine($header, $data);

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
                        'copyright_year'      => $this->safeInt($row['copyright_year'] ?? ''),
                        'pages'               => $this->safeInt($row['pages'] ?? ''),
                        'quantity'            => $this->safeInt($row['quantity'] ?? ''),
                        'volume'              => $this->safeInt($row['volume'] ?? ''),
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ];

                    $rows[] = $prepared;

                    // Insert in batches of 500 to manage memory
                    if (count($rows) >= 500) {
                        DB::table('imported_schools')->insert($rows);
                        $successCount += count($rows);
                        $rows = [];
                    }
                }

                fclose($handle);

                // Insert remaining rows
                if (!empty($rows)) {
                    DB::table('imported_schools')->insert($rows);
                    $successCount += count($rows);
                }

                // Log success
                Log::info("CSV Import completed: {$this->originalName}", [
                    'batch_id' => $this->batchId,
                    'records' => $successCount
                ]);

                // Clean up file after successful import
                Storage::delete($this->filePath);

            } else {
                throw new \Exception("Failed to open file: {$this->originalName}");
            }

        } catch (\Exception $e) {
            Log::error("CSV Import Job failed: {$this->originalName}", [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Convert value to integer or null
     */
    private function safeInt($value)
    {
        $trimmed = trim($value ?? '');
        return $trimmed === '' ? null : (int) $trimmed;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("CSV Import Job permanently failed: {$this->originalName}", [
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage()
        ]);

        // Optional: Send notification to admin about failed import
        // Notification::route('mail', config('mail.admin'))->notify(new ImportFailedNotification($this->originalName));
    }
}
