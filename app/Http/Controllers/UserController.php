<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{

   public function getUsers(Request $request){

        $perPage = $request->query("per_page", "10");

        $page = $request->query("page",0);

        $offset= $page * $perPage;

        $products = User::skip($offset)->take($perPage)->get();

        //Respondemos con nuestra consulta
        return response()->json($products);
    }

    public function getUser($id)
    {
        $user = User::findOrFail($id); // 404 si no existe

        return response()->json([
            'message' => 'Usuario obtenido correctamente',
            'data'    => $user
        ]);
    }

      public function update(UserRequest $request, $id){
        try{
            $validatedData = $request->validated();
            $user = User::findOrFail($id);
            $user->update($validatedData);

            return response()->json(
                ["message" => "Usuario editado exitosamente", "Usuario" => $user]);
            }catch(Exception $e){
                return response()->json([
                    "error" => $e
                ],500);
            }
    }

    public function delete($id)
    {
        $user = User::findOrFail($id); // 404 si no existe
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }

}
