<?php

namespace App\Http\Controllers\Station;
use App\Http\Controllers\Controller;

use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Population;

use Illuminate\Http\Request;
use App\Models\GradeOffering;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class SchoolController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $schoolId = Auth::user()->station_id;
        $school = School::where('id', Auth::user()->station_id)->firstOrFail();

        // Get grade offering for this school (may be null if not created yet)
        $gradeOffering = GradeOffering::where('school_id', $schoolId)->first();

        // Get all school years for dropdown
        $schoolYears = SchoolYear::orderBy('year_end', 'desc')->get();

        // Get selected school year ID from request or default to null
        $selectedSyId = $request->query('sy_id');

        // Get population data for selected school year (if any)
        $population = null;
        if ($selectedSyId) {
            $population = Population::where('school_id', $schoolId)
                ->where('sy_id', $selectedSyId)
                ->first();
        }

        return view('pages.school-profile', compact('school', 'gradeOffering', 'schoolYears', 'selectedSyId', 'population'));
    }

    /**
     * Get population data for a specific school year (AJAX endpoint)
     */
    public function getPopulationData($syId)
    {
        $schoolId = Auth::user()->station_id;
        
        // Validate school year exists
        $schoolYear = SchoolYear::find($syId);
        if (!$schoolYear) {
            return response()->json([
                'success' => false,
                'message' => 'School year not found'
            ], 404);
        }
        
        // Get grade offerings
        $gradeOffering = GradeOffering::where('school_id', $schoolId)->first();
        
        // Get population data
        $population = Population::where('school_id', $schoolId)
            ->where('sy_id', $syId)
            ->first();
        
        return response()->json([
            'success' => true,
            'gradeOffering' => $gradeOffering,
            'population' => $population,
        ]);
    }

    public function update(Request $request)
    {
        $school = School::where('id', Auth::user()->station_id)->firstOrFail();

        if ($request->filled('date_establish')) {
            $request->merge([
                'date_establish' => date('Y-m-d', strtotime($request->date_establish))
            ]);
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'email'       => 'required|email:rfc,dns|max:255',

            'shortname' => 'nullable|string|max:50',
            'contact_number' => [
                'nullable',
                function ($attr, $value, $fail) use ($request) {
                    $clean = preg_replace('/\s+|-/', '', $value);
                    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $clean)) {
                        $fail('Contact number must be a valid Philippine mobile number.');
                    }
                    $request->merge(['contact_number' => $clean]);
                }
            ],
            'date_establish' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'legislative_district' => 'nullable|string|max:255',
        ]);

        $school->fill($validated);

        if (!$school->isDirty()) {
            return redirect()
                ->route('school-profile')
                ->with('info', 'No changes were made.');
        }

        $school->save();

        return redirect()->route('school-profile')
        ->with('success', 'School information updated successfully.');
    }

    public function updateLogo(Request $request)
    {
        $school = School::where('id', Auth::user()->station_id)->firstOrFail();

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        // Delete old logo if exists
        if ($school->logo && Storage::disk('public')->exists($school->logo)) {
            Storage::disk('public')->delete($school->logo);
        }

        // Store new logo
        $logoPath = $request->file('logo')->store('school-logo', 'public');

        // Update school logo
        $school->logo = $logoPath;
        $school->save();

        return redirect()->route('school-profile')
            ->with('success', 'School logo updated successfully.');
    }

    public function updateGrades(Request $request)
    {
        // Get the authenticated user's station_id
        $schoolId = Auth::user()->station_id;

        // Validate each grade field
        $validated = $request->validate([
            'K' => 'nullable|in:yes',
            'g1' => 'nullable|in:yes',
            'g2' => 'nullable|in:yes',
            'g3' => 'nullable|in:yes',
            'g4' => 'nullable|in:yes',
            'g5' => 'nullable|in:yes',
            'g6' => 'nullable|in:yes',
            'g7' => 'nullable|in:yes',
            'g8' => 'nullable|in:yes',
            'g9' => 'nullable|in:yes',
            'g10' => 'nullable|in:yes',
            'g11' => 'nullable|in:yes',
            'g12' => 'nullable|in:yes',
        ]);

        // Prepare data (unchecked checkboxes won't be in request, so default to 'no')
        $gradeData = [
            'K' => $request->has('K') ? 'yes' : 'no',
            'g1' => $request->has('g1') ? 'yes' : 'no',
            'g2' => $request->has('g2') ? 'yes' : 'no',
            'g3' => $request->has('g3') ? 'yes' : 'no',
            'g4' => $request->has('g4') ? 'yes' : 'no',
            'g5' => $request->has('g5') ? 'yes' : 'no',
            'g6' => $request->has('g6') ? 'yes' : 'no',
            'g7' => $request->has('g7') ? 'yes' : 'no',
            'g8' => $request->has('g8') ? 'yes' : 'no',
            'g9' => $request->has('g9') ? 'yes' : 'no',
            'g10' => $request->has('g10') ? 'yes' : 'no',
            'g11' => $request->has('g11') ? 'yes' : 'no',
            'g12' => $request->has('g12') ? 'yes' : 'no',
        ];

        // Update or create grade offering
        $gradeOffering = GradeOffering::updateOrCreate(
            ['school_id' => $schoolId],
            $gradeData
        );

        // Check if it was recently created
        $message = $gradeOffering->wasRecentlyCreated
            ? 'Grade offerings created successfully!'
            : 'Grade offerings updated successfully!';

        return redirect()->route('school-profile')->with('success', $message);
    }

    public function updatePopulation(Request $request)
    {
        $schoolId = Auth::user()->station_id;

        // Validate school year
        $request->validate([
            'sy_id' => 'required|exists:school_years,id',
        ]);

        $syId = $request->input('sy_id');

        // Get grade offerings to determine which grades to validate
        $gradeOffering = GradeOffering::where('school_id', $schoolId)->first();

        if (!$gradeOffering) {
            return redirect()->route('school-profile')
                ->with('info', 'Please set up grade offerings first.');
        }

        // Build validation rules dynamically based on offered grades
        $validationRules = [];
        $populationData = [
            'school_id' => $schoolId,
            'sy_id' => $syId,
            'encoded_by' => Auth::id(),
        ];

        $grades = [
            'K' => ['k_m', 'k_f', 'k_total'],
            'g1' => ['g1_m', 'g1_f', 'g1_total'],
            'g2' => ['g2_m', 'g2_f', 'g2_total'],
            'g3' => ['g3_m', 'g3_f', 'g3_total'],
            'g4' => ['g4_m', 'g4_f', 'g4_total'],
            'g5' => ['g5_m', 'g5_f', 'g5_total'],
            'g6' => ['g6_m', 'g6_f', 'g6_total'],
            'g7' => ['g7_m', 'g7_f', 'g7_total'],
            'g8' => ['g8_m', 'g8_f', 'g8_total'],
            'g9' => ['g9_m', 'g9_f', 'g9_total'],
            'g10' => ['g10_m', 'g10_f', 'g10_total'],
            'g11' => ['g11_m', 'g11_f', 'g11_total'],
            'g12' => ['g12_m', 'g12_f', 'g12_total'],
        ];

        foreach ($grades as $gradeKey => $fields) {
            if ($gradeOffering->{$gradeKey} === 'yes') {
                foreach ($fields as $field) {
                    $validationRules[$field] = 'nullable|integer|min:0';
                    $populationData[$field] = $request->input($field, 0);
                }
            }
        }

        // Validate the request
        $request->validate($validationRules);

        // Check if population record exists for this school and school year
        $population = Population::where('school_id', $schoolId)
            ->where('sy_id', $syId)
            ->first();

        if ($population) {
            // Update existing record
            $population->update($populationData);
            $message = 'Population data updated successfully!';
        } else {
            // Create new record with UUID
            $populationData['id'] = (string) Str::uuid();
            Population::create($populationData);
            $message = 'Population data created successfully!';
        }

        return redirect()->route('school-profile', ['sy_id' => $syId])
            ->with('success', $message);
    }
}