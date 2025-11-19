<?php

namespace App\Http\Controllers;

use App\Events\UserRegistered;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(UserRequest $request)
    {
        $validated_data = $request->validated();

        $user= User::create([
            'name' => $validated_data['name'],
            'email' => $validated_data['email'],
            'password'=> bcrypt($validated_data['password'])
        ]);

        return response()->json(["message" => "Usuario registrado correctamente"],Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request)
    {
        $validated_data = $request->validated();

        $credentials= ['email' => $validated_data["email"],
                        'password' => $validated_data["password"]];

        try{

            if(!$token= JWTAuth::attempt($credentials)){
                return response()->json(["error" => "Correo o contraseña inválidos"],Response::HTTP_UNAUTHORIZED);
            }

           }catch(JWTException){
            return response()->json(["error" => "No se pudo generar el token"],500);
        }

        return $this->respondWithToken($token);
    }

    public function who(){

        $user= auth()->user();
        return response()->json($user);
    }

    public function logout(){
        try{
            $token= JWTAuth::getToken();

            JWTAuth::invalidate($token);

            return response()->json(["message" => "Sesión cerrada correctamente"]);
        }catch(JWTException $e){
            return response()->json(["error" =>" No se pudo cerrar la sesion, el token es invalido"],500);
        }
    }

    public function refresh(){
        try{
            $token = JWTAuth::getToken();

            $newToken = auth()->refresh();

            JWTAuth::invalidate($token);

            return $this->respondWithToken($newToken);

        }catch(JWTException $e){
            return response()->json(["error" => "Error al generar de nuevo el token"],500);
        }
    }



    protected function respondWithToken($token){

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL()*60
        ]);
    }


}
