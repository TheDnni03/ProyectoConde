<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware("jwt.auth")->group(function(){
    Route::get('/who',[AuthController::class,"who"]);

    Route::post('/logout',[AuthController::class,"logout"]);

    Route::post('/refresh',[AuthController::class,"refresh"]);

    Route::get('/users', [UserController::class, "getUsers"]);

    Route::get('/users/{id}', [UserController::class, "getUser"]);

    Route::put("/users/{id}",[UserController::class, "update"]);

    Route::delete("/users/{id}",[UserController::class, "delete"]);

});
