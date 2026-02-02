<?php

namespace App\Observers;

use App\Models\RegionLibrary;
use Illuminate\Support\Facades\DB;

class RegionLibraryObserver
{
    public function saved(RegionLibrary $library): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE library_id = ?
        ', [$library->id]);
    }
}
