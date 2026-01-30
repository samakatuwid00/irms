<?php

namespace App\Http\Controllers;

use App\Models\District;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class DistrictController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $district = District::where('id', Auth::user()->station_id)->firstOrFail();
        return view('pages.district-profile', compact('district'));
    }

    public function update(Request $request)
    {
        $district = District::where('id', Auth::user()->station_id)->firstOrFail();

        if ($request->filled('date_establish')) {
            $request->merge([
                'date_establish' => date('Y-m-d', strtotime($request->date_establish))
            ]);
        }

        $validated = $request->validate([
            'district_name' => 'required|string|max:255',
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

        $district->fill($validated);

        if (!$district->isDirty()) {
            return redirect()
                ->route('district-profile')
                ->with('info', 'No changes were made.');
        }

        $district->save();

        return redirect()->route('district-profile')
        ->with('success', 'District information updated successfully.');
    }

    public function updateLogo(Request $request)
    {
        $district = District::where('id', Auth::user()->station_id)->firstOrFail();

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        // Delete old logo if exists
        if ($district->logo && Storage::disk('public')->exists($district->logo)) {
            Storage::disk('public')->delete($district->logo);
        }

        // Store new logo
        $logoPath = $request->file('logo')->store('district-logo', 'public');

        // Update district logo
        $district->logo = $logoPath;
        $district->save();

        return redirect()->route('district-profile')
            ->with('success', 'District logo updated successfully.');
    }
}
