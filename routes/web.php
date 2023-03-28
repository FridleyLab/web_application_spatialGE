<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SpatialGeController;
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
    Route::get('/projects/{project}',[ProjectController::class, 'open'])->name('open-project')->middleware('project');
    Route::get('/projects/{project}/edit',[ProjectController::class, 'edit'])->name('edit-project')->middleware('project');
    Route::get('/projects/{project}/go-to-step/{step}',[ProjectController::class, 'go_to_step'])->name('go-to-step')->middleware('project');
    Route::patch('/projects/{project}',[ProjectController::class, 'update'])->name('update-project')->middleware('project');
    Route::get('/projects/{project}/import-data',[ProjectController::class, 'import_data'])->name('import-data')->middleware('project');
    Route::get('/projects/{project}/qc-data-transformation',[ProjectController::class, 'qc_data_transformation'])->name('qc-data-transformation')->middleware('project');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('destroy-project');

    Route::get('/projects/{project}/init-stlist', [ProjectController::class, 'createStList'])->name('create-stlist');

    Route::post('/projects/{project}/qc/filter', [ProjectController::class, 'applyFilter'])->name('qc-dt-filter');
    Route::post('/projects/{project}/qc/filter-plots', [ProjectController::class, 'generateFilterPlots'])->name('qc-dt-filter-plots');

    Route::get('/projects/{project}/search-genes',[ProjectController::class, 'searchGenes'])->name('search-genes')->middleware('project');
    Route::get('/projects/{project}/search-genes-regexp',[ProjectController::class, 'searchGenesRegexp'])->name('search-genes-regexp')->middleware('project');

    Route::post('/files', [FileController::class, 'store'])->name('file-upload');

    Route::post('/samples', [SampleController::class, 'store'])->name('store-sample');
    Route::delete('/samples/{sample}', [SampleController::class, 'destroy'])->name('destroy-sample');


});

Route::get('/login',[SecurityController::class, 'login'])->name('login')->middleware('guest');
Route::post('/login',[SecurityController::class, 'signin'])->middleware('guest');
Route::get('/signup',[SecurityController::class, 'signup'])->name('signup')->middleware('guest');
Route::post('/signup',[SecurityController::class, 'create'])->middleware('guest');
Route::get('/activate/{code}',[SecurityController::class, 'activate'])->middleware('guest')->name('account-activation');
Route::get('/activate/{user}/dev',[SecurityController::class, 'activateDev'])->middleware('guest')->name('account-activation-dev');
Route::get('/recover-password-email',[SecurityController::class, 'sendPasswordRecoveryEmail'])->middleware('guest')->name('send-password-recovery-email');
Route::get('/recover-password-email/dev',[SecurityController::class, 'sendPasswordRecoveryEmailDev'])->middleware('guest')->name('send-password-recovery-email-dev');
Route::get('/recover-password/{code}',[SecurityController::class, 'resetPasswordForm'])->middleware('guest')->name('password-recovery-form');

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
