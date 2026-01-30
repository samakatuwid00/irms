<?php

namespace App\Http\Controllers;

use App\Models\Division;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class DivisionController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $division = Division::where('id', Auth::user()->station_id)->firstOrFail();
        return view('pages.division-profile', compact('division'));
    }

    public function update(Request $request)
    {
        $division = Division::where('id', Auth::user()->station_id)->firstOrFail();

        if ($request->filled('date_establish')) {
            $request->merge([
                'date_establish' => date('Y-m-d', strtotime($request->date_establish))
            ]);
        }

        $validated = $request->validate([
            'division_name' => 'required|string|max:255',
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

        $division->fill($validated);

        if (!$division->isDirty()) {
            return redirect()
                ->route('division-profile')
                ->with('info', 'No changes were made.');
        }

        $division->save();

        return redirect()->route('division-profile')
        ->with('success', 'Division information updated successfully.');
    }

    public function updateLogo(Request $request)
    {
        $division = Division::where('id', Auth::user()->station_id)->firstOrFail();

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png|max:2048', // 2MB max
        ]);

        // Delete old logo if exists
        if ($division->logo && Storage::disk('public')->exists($division->logo)) {
            Storage::disk('public')->delete($division->logo);
        }

        // Store new logo
        $logoPath = $request->file('logo')->store('division-logo', 'public');

        // Update division logo
        $division->logo = $logoPath;
        $division->save();

        return redirect()->route('division-profile')
            ->with('success', 'Division logo updated successfully.');
    }
}
