<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTableDataRequest extends FormRequest
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
            'draw'         => ['nullable', 'integer'],
            'start'        => ['required', 'integer', 'min:0'],
            'length'       => ['required', 'integer', 'min:1', 'max:100'],
            'search.value' => ['nullable', 'string', 'max:100'],
        ];
    }
}
