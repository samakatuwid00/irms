<?php

namespace App\Services;

use App\Models\NonprintAcquisition;
use App\Models\NonprintResource;
use App\Models\PrintAcquisition;
use App\Models\PrintResource;
use App\Services\LibraryScopeService;
use Illuminate\Support\Collection;

class TotalLearningResourcesService
{
    private LibraryScopeService $libraryScopeService;

    public function __construct(LibraryScopeService $libraryScopeService)
    {
        $this->libraryScopeService = $libraryScopeService;
    }

    public function getTotalResourcesData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        $allowedLibraryIds = $this->libraryScopeService->getAllowedLibraryIds(
            $explicitLibraryId,
            $userLevel,
            $stationId
        );

        if ($allowedLibraryIds->isEmpty()) {
            return [
                'total'     => 0,
                'print'     => 0,
                'non_print' => 0
            ];
        }

        // Print resources - correct table names
        $printTotal = PrintResource::whereIn('library_id', $allowedLibraryIds)
            ->join('print_acquisitions', 'print_resources.id', '=', 'print_acquisitions.print_id')
            ->sum('print_acquisitions.total_qty');

        // Non-print resources - FIXED: use correct table name without underscore
        $nonPrintTotal = NonprintResource::whereIn('library_id', $allowedLibraryIds)
            ->join('nonprint_acquisitions', 'nonprint_resources.id', '=', 'nonprint_acquisitions.nonprint_id')
            ->sum('nonprint_acquisitions.total_qty');

        $total = $printTotal + $nonPrintTotal;

        return [
            'total'     => (int) $total,
            'print'     => (int) $printTotal,
            'non_print' => (int) $nonPrintTotal
        ];
    }

    public function getPopulationData(?string $explicitLibraryId, int $userLevel, ?string $stationId): array
    {
        // Dummy data - you can replace this later with real DB queries
        return [
            'total'  => 3500,
            'male'   => 180,
            'female' => 170,
        ];
    }
}