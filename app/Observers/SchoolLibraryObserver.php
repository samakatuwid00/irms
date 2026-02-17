<?php

namespace App\Observers;

use App\Models\SchoolLibrary;
use Illuminate\Support\Facades\DB;

class SchoolLibraryObserver
{
    public function saved(SchoolLibrary $library): void
    {
        // Keep library_name column in sync on both resource tables
        DB::statement('
            UPDATE print_resources
            SET library_name = ?
            WHERE library_id = ?
        ', [$library->library_name, $library->id]);

        DB::statement('
            UPDATE nonprint_resources
            SET library_name = ?
            WHERE library_id = ?
        ', [$library->library_name, $library->id]);

        // Rebuild search vectors
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE library_id = ?
        ', [$library->id]);

        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE library_id = ?
        ', [$library->id]);
    }
}
