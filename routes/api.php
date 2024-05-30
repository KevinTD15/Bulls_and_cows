<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')-> get('/user', function (Request $request){
    return $request->user();
});

Route::post('register',[AuthController::class,'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('homepage',[HomeController::class,'homepage'])->name('login');

Route::middleware(['auth:sanctum'])->group(function (){
    Route::post('logout',[AuthController::class,'logout']);

    Route::group(['prefix'=> 'game'], function (){
        Route::post('index',[GameController::class,'index']);
        Route::get('ranking',[GameController::class,'ranking']);
        Route::post('create_game', [GameController::class,'create_game']);
        Route::post('execute_turn', [GameController::class,'execute_turn']);
    });
});