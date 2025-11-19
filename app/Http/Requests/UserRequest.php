<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends ApiFormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|max:255|unique:users',
            'password' => 'required|min:8|string'
        ];
    }

    public function messages(){
        return [
            // name
            'name.required' => 'El nombre es obligatorio',
            'name.string'   => 'El nombre debe ser una cadena de texto',
            'name.max'      => 'El nombre no puede superar los 255 caracteres',

            // email
            'email.required' => 'El correo es obligatorio',
            'email.email'    => 'El correo debe ser una dirección de correo válida',
            'email.string'   => 'El correo debe ser una cadena de texto',
            'email.max'      => 'El correo no puede superar los 255 caracteres',
            'email.unique'   => 'Este correo ya está registrado',

            // password
            'password.required' => 'La contraseña es obligatoria',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres',
            'password.string'   => 'La contraseña debe ser una cadena de texto',
    ];
    }
}
