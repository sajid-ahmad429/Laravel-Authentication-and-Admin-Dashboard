<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->input('user_id');
        $isUpdating = $this->has('user_id') && !empty($userId) && $userId != 0;

        return [
            'userFullname' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-]+$/'],
            'userEmail'    => ['required', 'email', $isUpdating ? 'unique:users,email,' . $userId : 'unique:users,email'],
            'userContact'  => ['required', 'string', 'max:10', $isUpdating ? 'unique:users,contact_no,' . $userId : 'unique:users,contact_no'],
            'companyName'  => ['nullable', 'string', 'max:150'],
            'country'      => ['nullable', 'string', 'max:100'],
            'user-role'    => ['nullable', 'string', 'exists:roles,name'],
            'user-plan'    => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 0,
            'message' => 'Validation error occurred.',
            'errors'  => $validator->errors()
        ], 422));
    }
}
