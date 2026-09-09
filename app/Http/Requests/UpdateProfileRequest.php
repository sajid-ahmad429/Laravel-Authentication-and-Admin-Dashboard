<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        return [
            'name'       => ['required', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:15'],
            'company'    => ['nullable', 'string', 'max:150'],
            'country'    => ['nullable', 'string', 'max:100'],
            'avatar'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
