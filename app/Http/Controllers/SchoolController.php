<?php

namespace App\Http\Controllers;

use App\Models\School;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function index()
    {
        $school = School::where('id', Auth::user()->station_id)->firstOrFail();
        return view('pages.school-profile', compact('school'));
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
}
