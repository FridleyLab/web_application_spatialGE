<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\FileController;
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



    //Project CRUD
    Route::get('/projects',[ProjectController::class, 'index'])->name('my-projects');
    Route::post('/projects',[ProjectController::class, 'store'])->name('store-project');
    Route::get('/projects/new',[ProjectController::class, 'create'])->name('new-project');
    Route::get('/projects/{project}/import-data',[ProjectController::class, 'import_data'])->name('import-data');
    Route::post('/file-upload', [FileController::class, 'create'])->name('file-upload');

});

Route::get('/login',[SecurityController::class, 'login'])->name('login')->middleware('guest');
Route::post('/login',[SecurityController::class, 'signin'])->middleware('guest');
Route::get('/signup',[SecurityController::class, 'signup'])->name('signup')->middleware('guest');
Route::post('/signup',[SecurityController::class, 'create'])->middleware('guest');
Route::get('/activate/{code}',[SecurityController::class, 'activate'])->middleware('guest')->name('account-activation');

//Route::get('/test-login',[SecurityController::class, 'testLogIn'])->name('test-login')->middleware('guest');



//Homepage & info
Route::name('how-to')->get('/how-to-get-started', function () {
    return view('how-to-get-started');
});
Route::name('faq')->get('/faq', function () {
    return view('faq');
});
Route::name('home')->get('/', function () {
    return view('home');
});
