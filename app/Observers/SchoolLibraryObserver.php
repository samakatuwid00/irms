<?php

namespace App\Observers;

use App\Models\SchoolLibrary;
use Illuminate\Support\Facades\DB;

class SchoolLibraryObserver
{
    public function saved(SchoolLibrary $library): void
    {
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
            WHERE library_id = ?
        ', [$library->id]);
    }
}
