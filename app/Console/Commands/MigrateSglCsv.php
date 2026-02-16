<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSglCsv extends Command
{
    // ← THIS is the Artisan command name
    protected $signature = 'migrate:sgl-csv';

    protected $description = 'Populate print_resource_sgl pivot table from subject_grade_level_ids';

    public function handle()
    {
        $resources = DB::table('print_resources')->get();
        $count = 0;

        foreach ($resources as $r) {
            if (empty($r->subject_grade_level_ids)) {
                continue; // skip if no SGL ids
            }

            // Split by comma and trim spaces
            $ids = array_map('trim', array_filter(explode(',', $r->subject_grade_level_ids)));

            foreach ($ids as $sglId) {
                if (empty($sglId)) {
                    continue;
                }

                DB::table('print_resource_sgl')->insert([
                    'print_id' => $r->id,
                    'sgl_id'   => $sglId,
                ]);

                $count++;
            }
        }

        $this->info("Inserted rows: $count");
    }

}
