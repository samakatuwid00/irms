<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\{UserType, Division, District, School};

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname'        => 'required|string|max:255',
            'lastname'         => 'required|string|max:255',
            'middlename'       => 'nullable|string|max:255',
            'extension_name'   => 'nullable|string|in:,Jr,Sr,II,III,IV,V,VI',
            'gender'           => 'required|in:male,female,other',
            'birthday'         => 'nullable|date|before:today',
            'username'         => 'required|string|min:4|max:50|alpha_dash|unique:users,username',
            'password'         => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'email'            => 'required|email:rfc,dns|max:255|unique:users,email',
            'contact_number'   => ['required', 'regex:/^09\d{9}$/'],
            'usertype'         => 'required|exists:usertypes,id',
            'authority_level'  => 'required|in:1,2,3,4',
            'region'           => 'required_if:authority_level,4,3,2,1|nullable|exists:regions,id',
            'division'         => 'required_if:authority_level,3,2,1|nullable|exists:divisions,id',
            'district'         => 'required_if:authority_level,2,1|nullable|exists:districts,id',
            'school'           => 'required_if:authority_level,1|nullable|exists:schools,id',
            'agree'            => 'accepted',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateUsertypeLevelMatch($validator);
            $this->validateStationHierarchy($validator);
        });
    }

    protected function validateUsertypeLevelMatch($validator): void
    {
        if ($this->filled('usertype') && $this->filled('authority_level')) {
            $usertype = UserType::find($this->usertype);

            if ($usertype && (int) $usertype->level !== (int) $this->authority_level) {
                $validator->errors()->add(
                    'authority_level',
                    'The selected authority level does not match the user type.'
                );
            }
        }
    }

    protected function validateStationHierarchy($validator): void
    {
        // Division must belong to the selected region
        if ($this->filled(['division', 'region'])) {
            $division = Division::find($this->division);
            if ($division && $division->region_id !== $this->region) {
                $validator->errors()->add(
                    'division',
                    'The selected division does not belong to the selected region.'
                );
            }
        }

        // District must belong to the selected division
        if ($this->filled(['district', 'division'])) {
            $district = District::find($this->district);
            if ($district && $district->division_id !== $this->division) {
                $validator->errors()->add(
                    'district',
                    'The selected district does not belong to the selected division.'
                );
            }
        }

        // School must belong to the selected district
        if ($this->filled(['school', 'district'])) {
            $school = School::find($this->school);
            if ($school && $school->district_id !== $this->district) {
                $validator->errors()->add(
                    'school',
                    'The selected school does not belong to the selected district.'
                );
            }
        }
    }

    public function messages(): array
    {
        return [
            'password.min'              => 'Password must be at least 8 characters.',
            'contact_number.regex'      => 'Enter a valid Philippine mobile number (e.g. 09123456789).',
            'extension_name.in'         => 'Invalid name extension selected.',
            'username.alpha_dash'       => 'Username may only contain letters, numbers, dashes, and underscores.',
            'username.min'              => 'Username must be at least 4 characters.',
            'email.email'               => 'Please enter a valid email address.',
            'birthday.before'           => 'Birthday must be a date in the past.',
        ];
    }
}
