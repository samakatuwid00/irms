<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
        $regions = Region::getRegions()->getData();
        $divisions = Division::getDivisions()->getData();
        $districts = District::getDistricts()->getData();
        $schools = School::getSchools()->getData();
        $usertypes = Usertype::getUsertypes()->getData();

        return view('register', compact(
            'regions',
            'divisions',
            'districts',
            'schools',
            'usertypes'

        ));
    }

    public function submitRegistration(Request $request)
    {
        $request->validate([
            'firstname'        => 'required|string|max:255',
            'lastname'         => 'required|string|max:255',
            'middlename'       => 'nullable|string|max:255',
            'extension_name'   => 'nullable|string|max:10',
            'gender'           => 'required|in:male,female,other',
            'birthday'         => 'nullable|date',
            'username'         => 'required|string|max:50|unique:users,username',
            'password'         => 'required|string|min:8|confirmed',
            'email'            => 'required|email|max:255|unique:users,email',
            'contact_number' => ['required', 'regex:/^09\d{9}$/'],
            'usertype'         => 'required|exists:usertypes,id',
            'authority_level'  => 'required|in:1,2,3,4',
            'region'           => 'required_if:authority_level,4,3,2,1|nullable|exists:regions,id',
            'division'         => 'required_if:authority_level,3,2,1|nullable|exists:divisions,id',
            'district'         => 'required_if:authority_level,2,1|nullable|exists:districts,id',
            'school'           => 'required_if:authority_level,1|nullable|exists:schools,id',
            'agree'            => 'accepted',
        ]);

        DB::beginTransaction();
        try {
            $userId = Str::uuid()->toString();

            User::create([
                'id'             => Str::uuid()->toString(),
                'firstname'      => $request->firstname,
                'middlename'     => $request->middlename,
                'lastname'       => $request->lastname,
                'extension_name' => $request->extension_name,
                'gender'         => $request->gender,
                'birthday'       => $request->birthday,
                'username'       => $request->username,
                'password'       => Hash::make($request->password),
                'email'          => $request->email,
                'contact_number' => $request->contact_number,
                'usertype_id'    => $request->usertype,
                'station_id'     => $request->school ?? $request->district ?? $request->division ?? $request->region ?? null,
                'status'         => 'pending',
                'approved_by'    => null,
            ]);


            DB::commit();
            return redirect()->route('register')->with('success', 'Account registered successfully! Await approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Registration failed: '.$e->getMessage()]);
        }
    }
}
