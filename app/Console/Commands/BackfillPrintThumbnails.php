<?php

namespace App\Console\Commands;

use App\Models\PrintResource;
use App\Services\Resource\Actions\AddPrintResourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillPrintThumbnails extends Command
{
    protected $signature   = 'print:backfill-thumbnails {--force : Regenerate even if thumbnail already exists}';
    protected $description = 'Generate missing ≤20 KB thumbnails for existing print resource cover images';

    public function handle(AddPrintResourceService $service): int
    {
        $force = $this->option('force');
        $disk  = Storage::disk('public');

        $resources = PrintResource::whereNotNull('cover')->get();

        if ($resources->isEmpty()) {
            $this->info('No print resources with cover images found.');
            return self::SUCCESS;
        }

        $this->info("Found {$resources->count()} resource(s) with covers. Generating thumbnails…");
        $bar = $this->output->createProgressBar($resources->count());
        $bar->start();

        $generated = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($resources as $resource) {
            $thumbPath = $service->thumbnailPathFromCover($resource->cover);

            if (!$thumbPath) {
                $bar->advance();
                continue;
            }

            if (!$force && $disk->exists($thumbPath)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$disk->exists($resource->cover)) {
                // Cover file missing on disk — nothing to thumbnail
                $failed++;
                $bar->advance();
                continue;
            }

            try {
                $ref    = new \ReflectionMethod($service, 'generateThumbnail');
                $ref->setAccessible(true);
                $ref->invoke($service, $resource->cover);
                $generated++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("  Failed for [{$resource->id}]: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(
            ['Generated', 'Skipped (exists)', 'Failed'],
            [[$generated, $skipped, $failed]]
        );

        return self::SUCCESS;
    }
}