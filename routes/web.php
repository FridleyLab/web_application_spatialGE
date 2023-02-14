<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SecurityController;
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


Route::middleware(['auth'])->group(function() {

    Route::get('/logout',[SecurityController::class, 'destroy'])->name('logout');
    Route::get('/dashboard',[HomeController::class, 'dashboard'])->name('dashboard');

});

Route::get('/login',[SecurityController::class, 'create'])->name('login')->middleware('guest');
Route::get('/test-login',[SecurityController::class, 'testLogIn'])->name('test-login')->middleware('guest');



//Homepage
Route::name('home')->get('/', function () {
    return view('home');
});
