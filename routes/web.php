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

    Route::get('/stats',[HomeController::class, 'download_statistics'])->name('stats');
    Route::get('/show-stats',[HomeController::class, 'show_statistics'])->name('show-stats');
    Route::get('/admin-download-file/{project}/{filename}',[HomeController::class, 'admin_download_file'])->name('show-stats');
    Route::get('/create-test-users/prefix/{prefix}/n_users/{n_users}/n_samples/{n_samples}',[HomeController::class, 'create_test_users'])->name('create-test-users');
    Route::get('/runNormalizationSim',[HomeController::class, 'runNormalization'])->name('runNormalizationSim');
    Route::get('/runSTclustSim/from/{from}/to/{to}',[HomeController::class, 'runSTclust'])->name('runSTclustSim');


    Route::get('/logout',[SecurityController::class, 'destroy'])->name('logout');

    Route::get('/dashboard',[HomeController::class, 'dashboard'])->name('dashboard');



    //Clone Demo Project
    Route::get('/projects/clone-demo-project/{platform}',[ProjectController::class, 'clone_demo_project'])->name('clone-demo-project');
    Route::get('/projects/clone-demo-project-cosmx',[ProjectController::class, 'create_cosmx_temp_project'])->name('clone-demo-project-cosmx');
    //Project CRUD
    Route::get('/projects',[ProjectController::class, 'index'])->name('my-projects');
    Route::post('/projects',[ProjectController::class, 'store'])->name('store-project');
    Route::get('/projects/new',[ProjectController::class, 'create'])->name('new-project');
    Route::get('/projects/{project}',[ProjectController::class, 'open'])->name('open-project')->middleware('project');
    Route::get('/projects/{project}/edit',[ProjectController::class, 'edit'])->name('edit-project')->middleware('project');
    Route::patch('/projects/{project}',[ProjectController::class, 'update'])->name('update-project')->middleware('project');
    Route::get('/projects/{project}/get-project-parameters',[ProjectController::class, 'getProjectParameters'])->name('get-project-parameters')->middleware('project');
    Route::get('/projects/{project}/get-stdiff-annotations',[ProjectController::class, 'getSTdiffAnnotations'])->name('get-stdiff-annotations')->middleware('project');
    Route::get('/projects/{project}/get-stdiff-annotations-by-sample/{method}',[ProjectController::class, 'getSTdiffAnnotationsBySample'])->name('get-stdiff-annotations-by-sample')->middleware('project');

    Route::get('/projects/{project}/go-to-step/{step}',[ProjectController::class, 'go_to_step'])->name('go-to-step')->middleware('project');

    Route::get('/projects/{project}/import-data',[ProjectController::class, 'import_data'])->name('import-data')->middleware('project');
    Route::post('/projects/{project}/read-metadata-from-excel-file',[ProjectController::class, 'readExcelMetadataFile'])->name('read-metadata-from-excel-file')->middleware('project');
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
    Route::get('/samples/{sample}/get-image', [SampleController::class, 'get_image'])->name('get-sample-image');
    Route::delete('/samples/{sample}', [SampleController::class, 'destroy'])->name('destroy-sample');
    Route::post('/samples/{sample}/rename', [SampleController::class, 'rename'])->name('rename-sample');



    Route::get('/projects/{project}/download-files/{process}.zip',[ProjectController::class, 'downloadJobFiles'])->name('download-job-files')->middleware('project');
    Route::get('/projects/{project}/get-job-position-in-queue',[ProjectController::class, 'getJobPositionInQueue'])->name('get-job-position-in-queue')->middleware('project');
    Route::get('/projects/{project}/get-job-parameters',[ProjectController::class, 'getParametersUsedInJob'])->name('get-job-parameters')->middleware('project');
    Route::get('/projects/{project}/set-job-email-notification',[ProjectController::class, 'setJobEmailNotification'])->name('set-job-email-notification')->middleware('project');
    Route::get('/projects/{project}/cancel-job-in-queue',[ProjectController::class, 'cancelJobInQueue'])->name('cancel-job-in-queue')->middleware('project');
    Route::get('/projects/{project}/get-jobs-in-queue',[ProjectController::class, 'getJobsInQueue'])->name('get-jobs-in-queue')->middleware('project');


    Route::get('/projects/{project}/stplot-visualization',[ProjectController::class, 'stplot_visualization'])->name('stplot-visualization')->middleware('project');
    Route::post('/projects/{project}/stplot/quilt', [ProjectController::class, 'stplot_quilt'])->name('stplot-quilt')->middleware('project');
    Route::post('/projects/{project}/stplot/expression-surface', [ProjectController::class, 'stplot_expression_surface'])->name('stplot-expression-surface')->middleware('project');
    Route::post('/projects/{project}/stplot/expression-surface-plots', [ProjectController::class, 'stplot_expression_surface_plots'])->name('stplot-expression-surface-plots')->middleware('project');

    Route::get('/projects/{project}/sthet-spatial-het',[ProjectController::class, 'sthet_spatial_het'])->name('sthet-spatial-het')->middleware('project');
    Route::post('/projects/{project}/sthet-spatial-het-calculate', [ProjectController::class, 'sthet_spatial_het_calculate'])->name('sthet-spatial-het-calculate')->middleware('project');
    Route::post('/projects/{project}/sthet-spatial-het-plot', [ProjectController::class, 'sthet_spatial_het_plot'])->name('sthet-spatial-het-plot')->middleware('project');

    Route::get('/projects/{project}/spatial-domain-detection',[ProjectController::class, 'spatial_domain_detection'])->name('spatial-domain-detection')->middleware('project');
    Route::post('/projects/{project}/sdd/stclust', [ProjectController::class, 'sdd_stclust'])->name('sdd-stclust')->middleware('project');
    Route::post('/projects/{project}/sdd/stclust-rename', [ProjectController::class, 'sdd_stclust_rename'])->name('sdd-stclust-rename')->middleware('project');
    Route::post('/projects/{project}/sdd/spagcn', [ProjectController::class, 'sdd_spagcn'])->name('sdd-spagcn')->middleware('project');
    Route::post('/projects/{project}/sdd/spagcn-svg', [ProjectController::class, 'sdd_spagcn_svg'])->name('sdd-spagcn-svg')->middleware('project');
    Route::post('/projects/{project}/sdd/spagcn-rename', [ProjectController::class, 'sdd_spagcn_rename'])->name('sdd-spagcn-rename')->middleware('project');
    Route::post('/projects/{project}/sdd/MILWRM', [ProjectController::class, 'sdd_milwrm'])->name('sdd-milwrm')->middleware('project');

    Route::get('/projects/{project}/differential-expression',[ProjectController::class, 'differential_expression'])->name('differential-expression')->middleware('project');
    Route::post('/projects/{project}/differential-expression/non-spatial', [ProjectController::class, 'differential_expression_non_spatial'])->name('differential-expression-non-spatial')->middleware('project');
    Route::post('/projects/{project}/differential-expression/spatial', [ProjectController::class, 'differential_expression_spatial'])->name('differential-expression-spatial')->middleware('project');

    Route::get('/projects/{project}/spatial-gene-set-enrichment',[ProjectController::class, 'spatial_gene_set_enrichment'])->name('spatial-gene-set-enrichment')->middleware('project');
    Route::post('/projects/{project}/spatial-gene-set-enrichment/stenrich',[ProjectController::class, 'spatial_gene_set_enrichment_stenrich'])->name('spatial-gene-set-enrichment-stenrich')->middleware('project');

    Route::get('/projects/{project}/spatial-gradients',[ProjectController::class, 'spatial_gradients'])->name('spatial-gradients')->middleware('project');
    Route::post('/projects/{project}/spatial-gradients/stgradients',[ProjectController::class, 'spatial_gradients_stgradients'])->name('spatial-gradients-stgradients')->middleware('project');

    Route::get('/projects/{project}/SPARK-X',[ProjectController::class, 'SPARK_X'])->name('sparkx')->middleware('project');
    Route::post('/projects/{project}/SPARK-X/SPARK',[ProjectController::class, 'SPARK'])->name('spark')->middleware('project');

    Route::get('/projects/{project}/phenotyping',[ProjectController::class, 'phenotyping'])->name('phenotyping')->middleware('project');
    Route::post('/projects/{project}/phenotyping/STdeconvolve', [ProjectController::class, 'STdeconvolve'])->name('STdeconvolve')->middleware('project');
    Route::post('/projects/{project}/phenotyping/STdeconvolve2', [ProjectController::class, 'STdeconvolve2'])->name('STdeconvolve2')->middleware('project');
    Route::post('/projects/{project}/phenotyping/STdeconvolve3', [ProjectController::class, 'STdeconvolve3'])->name('STdeconvolve3')->middleware('project');
    Route::post('/projects/{project}/phenotyping/InSituType', [ProjectController::class, 'InSituType'])->name('InSituType')->middleware('project');
    Route::post('/projects/{project}/phenotyping/InSituType2', [ProjectController::class, 'InSituType2'])->name('InSituType2')->middleware('project');
    Route::post('/projects/{project}/phenotyping/InSituTypeRename', [ProjectController::class, 'InSituTypeRename'])->name('InSituTypeRename')->middleware('project');

});

Route::get('/login',[SecurityController::class, 'login'])->name('login')->middleware('guest');
Route::post('/login',[SecurityController::class, 'signin'])->middleware('guest');
Route::get('/signup',[SecurityController::class, 'signup'])->name('signup')->middleware('guest');
Route::post('/signup',[SecurityController::class, 'create'])->middleware('guest');
Route::get('/activate/{code}',[SecurityController::class, 'activate'])->middleware('guest')->name('account-activation');
Route::get('/activate/{user}/dev',[SecurityController::class, 'activateDev'])->middleware('guest')->name('account-activation-dev');

Route::get('/recover-password-email/dev',[SecurityController::class, 'sendPasswordRecoveryEmailDev'])->middleware('guest')->name('send-password-recovery-email-dev');

Route::get('/recover-password-email',[SecurityController::class, 'sendPasswordRecoveryEmail'])->middleware('guest')->name('send-password-recovery-email');
Route::get('/recover-password/{code}',[SecurityController::class, 'resetPasswordForm'])->middleware('guest')->name('password-recovery-form');
Route::post('/recover-password/{user}',[SecurityController::class, 'changeUserPassword'])->middleware('guest')->name('change-user-password');


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
Route::name('contact-us')->get('/contact-us', function () {
    return view('contact-us');
});
Route::post('/contact-us',[HomeController::class, 'contactUs']);


Route::get('/zxc', function () {
    $project = \App\Models\Project::find(1);

    return (new \App\Notifications\ProcessCompleted($project, 'Templates testing', 'output generated', 'Script source code'))
        ->toMail($project->user);
});
