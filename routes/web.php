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
    Route::patch('/projects/{project}',[ProjectController::class, 'update'])->name('update-project')->middleware('project');
    Route::get('/projects/{project}/get-project-parameters',[ProjectController::class, 'getProjectParameters'])->name('get-project-parameters')->middleware('project');

    Route::get('/projects/{project}/go-to-step/{step}',[ProjectController::class, 'go_to_step'])->name('go-to-step')->middleware('project');

    Route::get('/projects/{project}/import-data',[ProjectController::class, 'import_data'])->name('import-data')->middleware('project');
    Route::post('/projects/{project}/save-metadata',[ProjectController::class, 'save_metadata'])->name('save-metadata')->middleware('project');
    Route::get('/projects/{project}/qc-data-transformation',[ProjectController::class, 'qc_data_transformation'])->name('qc-data-transformation')->middleware('project');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('destroy-project')->middleware('project');

    Route::get('/projects/{project}/init-stlist', [ProjectController::class, 'createStList'])->name('create-stlist')->middleware('project');

    Route::post('/projects/{project}/qc/filter', [ProjectController::class, 'applyFilter'])->name('qc-dt-filter')->middleware('project');
    Route::post('/projects/{project}/qc/filter-plots', [ProjectController::class, 'generateFilterPlots'])->name('qc-dt-filter-plots')->middleware('project');

    Route::post('/projects/{project}/qc/normalize', [ProjectController::class, 'applyNormalization'])->name('qc-dt-normalize')->middleware('project');
    Route::post('/projects/{project}/qc/normalize-plots', [ProjectController::class, 'generateNormalizationPlots'])->name('qc-dt-normalize-plots')->middleware('project');
    Route::post('/projects/{project}/qc/normalized-data', [ProjectController::class, 'generateNormalizationData'])->name('qc-dt-normalized-data')->middleware('project');

    Route::post('/projects/{project}/qc/pca', [ProjectController::class, 'applyPca'])->name('qc-dt-pca')->middleware('project');
    Route::post('/projects/{project}/qc/pca-plots', [ProjectController::class, 'pcaPlots'])->name('qc-dt-pca-plots')->middleware('project');
    Route::post('/projects/{project}/qc/quilt', [ProjectController::class, 'quiltPlot'])->name('qc-dt-quilt')->middleware('project');

    Route::get('/projects/{project}/search-genes',[ProjectController::class, 'searchGenes'])->name('search-genes')->middleware('project');
    Route::get('/projects/{project}/search-genes-regexp',[ProjectController::class, 'searchGenesRegexp'])->name('search-genes-regexp')->middleware('project');

    Route::post('/files', [FileController::class, 'store'])->name('file-upload');

    Route::post('/samples', [SampleController::class, 'store'])->name('store-sample');
    Route::delete('/samples/{sample}', [SampleController::class, 'destroy'])->name('destroy-sample');


    Route::get('/projects/{project}/get-job-position-in-queue',[ProjectController::class, 'getJobPositionInQueue'])->name('get-job-position-in-queue')->middleware('project');
    Route::get('/projects/{project}/set-job-email-notification',[ProjectController::class, 'setJobEmailNotification'])->name('set-job-email-notification')->middleware('project');



    Route::get('/projects/{project}/stplot-visualization',[ProjectController::class, 'stplot_visualization'])->name('stplot-visualization')->middleware('project');
    Route::post('/projects/{project}/stplot/quilt', [ProjectController::class, 'stplot_quilt'])->name('stplot-quilt')->middleware('project');
    Route::post('/projects/{project}/stplot/expression-surface', [ProjectController::class, 'stplot_expression_surface'])->name('stplot-expression-surface')->middleware('project');
    Route::post('/projects/{project}/stplot/expression-surface-plots', [ProjectController::class, 'stplot_expression_surface_plots'])->name('stplot-expression-surface-plots')->middleware('project');

    Route::get('/projects/{project}/sthet-spatial-het',[ProjectController::class, 'sthet_spatial_het'])->name('sthet-spatial-het')->middleware('project');
    Route::post('/projects/{project}/sthet-spatial-het/plot', [ProjectController::class, 'sthet_spatial_het_plot'])->name('sthet-spatial-het-plot')->middleware('project');

    Route::get('/projects/{project}/spatial-domain-detection',[ProjectController::class, 'spatial_domain_detection'])->name('spatial-domain-detection')->middleware('project');
    Route::post('/projects/{project}/sdd/stclust', [ProjectController::class, 'sdd_stclust'])->name('sdd-stclust')->middleware('project');

    Route::get('/projects/{project}/differential-expression',[ProjectController::class, 'differential_expression'])->name('differential-expression')->middleware('project');
    Route::post('/projects/{project}/differential-expression/non-spatial', [ProjectController::class, 'differential_expression_non_spatial'])->name('differential-expression-non-spatial')->middleware('project');

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
