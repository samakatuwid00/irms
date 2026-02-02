<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class ProfileController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        return view('pages.profile', compact('user'));
    }

    public function updateInfo(Request $request)
    {
        $user = Auth::user();

        $input = $request->only([
            'firstname','middlename','lastname','extension_name','username','email','contact_number'
        ]);

        $changed = [];
        foreach ($input as $key => $value) {
            if ($user->$key !== $value) {
                $changed[$key] = $value;
            }
        }

        if (empty($changed)) {
            return redirect()->route('profile')
                ->with('success', 'No changes detected.');
        }

        $rules = [];
        if (isset($changed['firstname'])) $rules['firstname'] = 'required|string|max:255';
        if (isset($changed['middlename'])) $rules['middlename'] = 'nullable|string|max:255';
        if (isset($changed['lastname'])) $rules['lastname'] = 'required|string|max:255';
        if (isset($changed['extension_name'])) $rules['extension_name'] = 'nullable|string|max:50';
        if (isset($changed['username'])) $rules['username'] = 'required|string|max:255|unique:users,username,' . $user->id;
        if (isset($changed['email'])) $rules['email'] = 'required|email|max:255|unique:users,email,' . $user->id;
        if (isset($changed['contact_number'])) $rules['contact_number'] = 'nullable|string|max:20';

        $validated = $request->validate($rules);

        $successFields = [];
        $failedFields = [];

        try {
            DB::transaction(function () use ($user, $validated, &$successFields, &$failedFields) {
                foreach ($validated as $field => $value) {
                    try {
                        $user->$field = $value;
                        $user->save();
                        $successFields[] = $field;
                    } catch (\Throwable $e) {
                        $failedFields[] = $field;
                    }
                }
            });

            // Build message
            $messages = [];
            if (!empty($successFields)) {
                $capitalized = array_map(fn($f) => ucfirst($f), $successFields);
                $messages[] = "Successfully updated: " . implode(', ', $capitalized);
            }
            if (!empty($failedFields)) {
                $capitalized = array_map(fn($f) => ucfirst($f), $failedFields);
                $messages[] = "Failed to update: " . implode(', ', $capitalized);
            }

            $status = empty($failedFields) ? 'success' : 'error';
            return redirect()->route('profile')
                ->with($status, implode('. ', $messages));


        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('profile')
                ->with('error', 'Unexpected error occurred. Please try again.');
        }
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        try {
            DB::transaction(function () use ($user, $request) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
            });

            return redirect()->route('profile')
                ->with('success', 'Password updated successfully.');

        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'password' => 'Failed to update password. Please try again.',
            ]);
        }
    }

    public function updatePhoto(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        try {
            DB::transaction(function () use ($user, $request) {
                // Delete old photo if exists
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }

                // Generate filename based on user's first and last name
                $firstname = Str::slug($user->firstname);
                $lastname = Str::slug($user->lastname);
                $extension = $request->file('photo')->getClientOriginalExtension();

                // Create filename: firstname_lastname.ext (e.g., john_doe.jpg)
                $filename = $firstname . '_' . $lastname . '.' . $extension;

                // Store new photo with custom filename
                $photoPath = $request->file('photo')->storeAs('user_pic', $filename, 'public');

                // Update user photo
                $user->photo = $photoPath;
                $user->save();
            });

            return redirect()->route('profile')
                ->with('success', 'Profile photo updated successfully.');

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('profile')
                ->with('error', 'Failed to update photo. Please try again.');
        }
    }
}
