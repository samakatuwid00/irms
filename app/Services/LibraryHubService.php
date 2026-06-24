<?php

namespace App\Services;

use App\Models\DivisionLibrary;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class LibraryHubService
{
    public function getDivisionLibraryHubs(string $divisionId, ?string $search = null): LengthAwarePaginator
    {
        $query = DivisionLibrary::query()
            ->with('librarianUser')
            ->where('division_id', $divisionId)
            ->orderBy('library_name');

        if ($search) {
            $search = strtolower($search);

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(library_name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('librarianUser', function ($userQuery) use ($search) {
                        $userQuery->whereRaw('LOWER(firstname) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                    });

                if (is_numeric($search)) {
                    $q->orWhere('estimated_resource', (int) $search);
                }
            });
        }

        return $query->paginate(10)->withQueryString();
    }

    public function getDivisionLibrarians(string $divisionId): Collection
    {
        return User::query()
            ->where('station_id', $divisionId)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function findDivisionHub(string $divisionId, string $libraryHubId): DivisionLibrary
    {
        return DivisionLibrary::query()
            ->where('division_id', $divisionId)
            ->where('id', $libraryHubId)
            ->firstOrFail();
    }

    public function createDivisionHub(string $divisionId, array $data): DivisionLibrary
    {
        return DivisionLibrary::create([
            'id' => (string) Str::uuid(),
            'division_id' => $divisionId,
            'librarian' => $data['librarian'],
            'library_name' => $data['library_name'],
            'estimated_resource' => $data['estimated_resource'] ?? 0,
        ]);
    }

    public function updateDivisionHub(DivisionLibrary $libraryHub, array $data): DivisionLibrary
    {
        $libraryHub->fill([
            'librarian' => $data['librarian'],
            'library_name' => $data['library_name'],
            'estimated_resource' => $data['estimated_resource'] ?? 0,
        ]);

        $libraryHub->save();

        return $libraryHub;
    }
}
