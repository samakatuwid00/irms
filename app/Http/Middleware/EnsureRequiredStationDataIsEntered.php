<?php

namespace App\Http\Middleware;

use App\Services\DivisionResourceRequirementService;
use App\Services\SchoolPopulationRequirementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredStationDataIsEntered
{
    private const SCHOOL_ALLOWED_ROUTES = [
        'school-profile',
        'school.profile.*',
        'school.logo.*',
        'school.grades.*',
        'school.population.*',
        'import.sf6.*',
        'logout',
    ];

    private const DIVISION_ALLOWED_ROUTES = [
        'division-profile',
        'division.profile.*',
        'division.logo.*',
        'division.library-hubs.*',
        'logout',
    ];

    public function __construct(
        private readonly SchoolPopulationRequirementService $schoolPopulationRequirement,
        private readonly DivisionResourceRequirementService $divisionResourceRequirement
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $level = (int) $user->userType?->level;

        if ($level === 1) {
            if ($request->routeIs(...self::SCHOOL_ALLOWED_ROUTES)
                || ! $this->schoolPopulationRequirement->isRequired($user)) {
                return $next($request);
            }

            return $this->blockedResponse(
                $request,
                SchoolPopulationRequirementService::NOTICE,
                'school-profile'
            );
        }

        if ($level === 3) {
            if ($request->routeIs(...self::DIVISION_ALLOWED_ROUTES)
                || ! $this->divisionResourceRequirement->isRequired($user)) {
                return $next($request);
            }

            return $this->blockedResponse(
                $request,
                DivisionResourceRequirementService::NOTICE,
                'division-profile'
            );
        }

        return $next($request);
    }

    private function blockedResponse(Request $request, string $message, string $routeName): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => route($routeName),
            ], 403);
        }

        return redirect()
            ->route($routeName)
            ->with('required_station_data', true);
    }
}
