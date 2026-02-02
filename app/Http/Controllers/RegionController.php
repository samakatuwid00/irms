<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class RegionController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $region = Region::where('id', Auth::user()->station_id)->firstOrFail();
        return view('pages.region-profile', compact('region'));
    }

    public function update(Request $request)
    {
        $region = Region::where('id', Auth::user()->station_id)->firstOrFail();

        if ($request->filled('date_establish')) {
            $request->merge([
                'date_establish' => date('Y-m-d', strtotime($request->date_establish))
            ]);
        }

        $validated = $request->validate([
            'region_name' => 'required|string|max:255',
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
        ]);

        $region->fill($validated);

        if (!$region->isDirty()) {
            return redirect()
                ->route('region-profile')
                ->with('info', 'No changes were made.');
        }


        $region->save();

        return redirect()->route('region-profile')
        ->with('success', 'Region information updated successfully.');
    }

    public function updateLogo(Request $request)
    {
        $region = Region::where('id', Auth::user()->station_id)->firstOrFail();

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        // Delete old logo if exists
        if ($region->logo && Storage::disk('public')->exists($region->logo)) {
            Storage::disk('public')->delete($region->logo);
        }

        // Store new logo
        $logoPath = $request->file('logo')->store('region-logo', 'public');

        // Update region logo
        $region->logo = $logoPath;
        $region->save();

        return redirect()->route('region-profile')
            ->with('success', 'Region logo updated successfully.');
    }
}
