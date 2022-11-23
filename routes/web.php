<?php

use App\Http\Controllers\petController;
use App\Http\Controllers\userController;
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
Route::get('/login', function () {
    return view('login');
});
Route::get('/symptomLog', function () {
    return view('symptomLog');
})->name('symptomLog');
Route::get('Login', [userController::class, 'doLogin'])->name('login');
Route::get('SymptomLogNotify', [petController::class, 'SymptomLogNotify'])->name('SymptomLogNotify');
Route::post('sendNotification',[userController::class,'sendNotification'])->name('sendNotification');
Route::get('Logout', [userController::class, 'loggingout'])->name('logout');
Route::post('save-token', [userController::class, 'saveToken'])->name('save-token');
