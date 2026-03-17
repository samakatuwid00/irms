<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{
    Region,
    Division,
    District,
    School,
    UserType,
    User
};

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        $regions   = Region::orderBy('region_name')->get(['id', 'region_name', 'shortname']);
        $divisions = Division::orderBy('division_name')->get(['id', 'division_name', 'shortname', 'region_id']);
        $districts = District::orderBy('district_name')->get(['id', 'district_name', 'shortname', 'division_id']);
        $schools   = School::orderBy('school_name')->get(['id', 'school_name', 'shortname', 'district_id', 'school_type']);
        $usertypes = UserType::where('level', '!=', 0)->orderBy('type_name')->get(['id', 'type_name', 'level']);

        return view('register', compact(
            'regions',
            'divisions',
            'districts',
            'schools',
            'usertypes'
        ));
    }

    public function submitRegistration(RegisterRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $stationId = $validated['school']
                ?? $validated['district']
                ?? $validated['division']
                ?? $validated['region']
                ?? null;

            $user = new User();
            $user->id             = Str::uuid()->toString();
            $user->firstname      = $validated['firstname'];
            $user->middlename     = $validated['middlename'] ?? null;
            $user->lastname       = $validated['lastname'];
            $user->extension_name = $validated['extension_name'] ?? null;
            $user->gender         = $validated['gender'];
            $user->birthday       = $validated['birthday'] ?? null;
            $user->username       = $validated['username'];
            $user->password       = Hash::make($validated['password']);
            $user->email          = $validated['email'];
            $user->contact_number = $validated['contact_number'];
            $user->usertype_id    = $validated['usertype'];
            $user->station_id     = $stationId;
            $user->status         = 'pending';
            $user->approved_by    = null;
            $user->save();

            DB::commit();
            return redirect()->route('register')->with('success', 'Account registered successfully! Await approval.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Registration failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Registration failed. Please try again later.']);
        }
    }
}
