<?php

namespace App\Services\Resource\Actions;

use App\Models\NonprintAcquisition;
use App\Models\NonprintMasterlist;
use App\Models\NonprintResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddAcquisitionToExistingNonPrintResourceService
{
    // library_id/library_name are embedded per-acquisition entry, not top-level fields —
    // one submission can add acquisitions across multiple libraries at once
    public function addAcquisitions(NonprintResource $resource, string $acquisitionsJson): void
    {
        DB::transaction(function () use ($resource, $acquisitionsJson) {
            $this->handleAcquisitions($acquisitionsJson, $resource->id);
        });

        // Rebuild outside the transaction so the committed rows are visible to the index
        $this->updateSearchVector($resource->id);
    }

    private function handleAcquisitions(string $acquisitionsJson, string $nonprintResourceId): void
    {
        $acquisitions = json_decode($acquisitionsJson, true);

        if (empty($acquisitions)) {
            return;
        }

        $statusMap = [
            'usable'            => 'USABLE',
            'partially_damaged' => 'PARTIALLY DAMAGED',
            'damaged'           => 'DAMAGED',
            'lost'              => 'LOST',
            'condemnable'       => 'CONDEMNABLE',
        ];

        $userId            = Auth::id();
        $now               = now();
        $masterlistInserts = [];

        foreach ($acquisitions as $acquisition) {
            $acquisitionId = (string) Str::uuid();

            NonprintAcquisition::create([
                'id'                => $acquisitionId,
                'nonprint_id'       => $nonprintResourceId,
                'library_id'        => $acquisition['library_id']   ?? null,
                'library_name'      => $acquisition['library_name'] ?? null,
                'source'            => $acquisition['source'],
                'date_acquired'     => $acquisition['date_acquired'],
                // Coerce empty strings to 0 so the DB column doesn't reject them
                'cost'              => $acquisition['cost'] !== '' ? $acquisition['cost'] : 0,
                'iar'               => $acquisition['iar'] !== '' ? $acquisition['iar'] : null,
                'usable'            => $acquisition['usable'] !== '' ? (int) $acquisition['usable'] : 0,
                'partially_damaged' => $acquisition['partially_damaged'] !== '' ? (int) $acquisition['partially_damaged'] : 0,
                'damaged'           => $acquisition['damaged'] !== '' ? (int) $acquisition['damaged'] : 0,
                'lost'              => $acquisition['lost'] !== '' ? (int) $acquisition['lost'] : 0,
                'condemnable'       => $acquisition['condemnable'] !== '' ? (int) $acquisition['condemnable'] : 0,
                'total_qty'         => $acquisition['total_quantity'] !== '' ? (int) $acquisition['total_quantity'] : 0,
                'remarks'           => $acquisition['remarks'] ?? null,
                'encoded_by'        => $userId,
                'date_encoded'      => $now,
            ]);

            // One masterlist row per individual item — qty=3 usable means 3 rows with status=USABLE
            foreach ($statusMap as $field => $statusName) {
                $qty = (int) ($acquisition[$field] ?? 0);
                for ($i = 0; $i < $qty; $i++) {
                    $masterlistInserts[] = [
                        'id'                      => (string) Str::uuid(),
                        'nonprint_acquisition_id' => $acquisitionId,
                        'status'                  => $statusName,
                    ];
                }
            }
        }

        // Batch in chunks of 500 to avoid hitting DB parameter limits
        if (!empty($masterlistInserts)) {
            foreach (array_chunk($masterlistInserts, 500) as $chunk) {
                NonprintMasterlist::insert($chunk);
            }
        }
    }

    private function updateSearchVector(string $nonprintResourceId): void
    {
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
            WHERE id = ?
        ', [$nonprintResourceId]);
    }
}
