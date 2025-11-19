<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ApiFormRequest extends FormRequest
{
    //Esta funcion de aqui se hace para que se puedan enviar los errores de validacion a la hora de interactuar con registros
    protected function failedValidation(Validator $validator){
        throw new HttpResponseException(
            response()->json([
            'message' => 'error de validación',
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
