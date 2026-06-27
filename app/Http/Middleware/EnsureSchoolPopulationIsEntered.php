<?php

namespace App\Http\Middleware;

use App\Services\SchoolPopulationRequirementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolPopulationIsEntered
{
    private const ALLOWED_ROUTES = [
        'school-profile',
        'school.profile.*',
        'school.logo.*',
        'school.grades.*',
        'school.population.*',
        'import.sf6.*',
        'logout',
    ];

    public function __construct(
        private readonly SchoolPopulationRequirementService $populationRequirement
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if (! $this->populationRequirement->isRequired($user)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => SchoolPopulationRequirementService::NOTICE,
                'redirect' => route('school-profile'),
            ], 403);
        }

        return redirect()
            ->route('school-profile')
            ->with('population_required', true);
    }
}
