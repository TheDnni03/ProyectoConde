<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends ApiFormRequest
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
            'email' => 'required|email:rfc,dns|string|max:255',
            'password' => 'required|min:8|string'
        ];
    }

    public function messages(){
        return [
            'email.required' => 'El correo es obligatorio',
            'password.required' => 'La contraseña es obligatoria'
        ];
    }
}
