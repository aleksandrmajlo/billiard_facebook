<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Facebook webhook
Route::post('/webhook_face',[\App\Http\Controllers\FaceController::class,'webhook_face']);

// Facebook Authorization
Route::get('face_bot',[\App\Http\Controllers\FaceBotController::class,'face_bot']);
Route::post('face_send_token',[\App\Http\Controllers\FaceBotTokenController::class,'face_send_token']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
