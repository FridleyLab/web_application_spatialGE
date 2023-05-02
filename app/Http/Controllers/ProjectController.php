<?php

namespace App\Http\Controllers;

use App\Jobs\RunScript;
use App\Models\ColorPalette;
use App\Models\Project;
use App\Models\ProjectGene;
use App\Models\User;
use Database\Seeders\ProjectStatusSeeder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{

    public function index() : View
    {
        $projects = Project::where('user_id', auth()->id())->orderByDesc('updated_at')->get();

        return view('projects.index', compact(['projects']));
    }

    public function open(Project $project) {

        setActiveProject($project);

        return redirect($project->getCurrentStepUrl());

        //return redirect()->route('import-data', ['project' => $project->id]);
    }

    public function create(): View
    {

        return view('wizard.new-project');

    }

    public function store() {

        $name = request('name');
        $description = request('description');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        try {
            $project = Project::create(['name' => $name, 'description' => $description, 'user_id' => auth()->id()]);

            setActiveProject($project);

            return response(route('import-data',['project' => $project->id]));
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function edit(Project $project) {

        return view('wizard.edit-project', compact(['project']));

    }

    public function update(Project $project) {
        $name = request('name');
        $description = request('description');

        if(strlen(trim($name)) < 4)
            return response('Name has to be at least 4 characters long', 400);

        $project->name = $name;
        $project->description = $description;

        $project->save();

        return response(route('my-projects'));
    }


    public function import_data(Project $project): View
    {
        $samples = $project->samples;

        return view('wizard.import-data')->with(compact('project', 'samples'));

    }

    public function qc_data_transformation(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.qc_data_transformation')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function destroy(Project $project) {

        $project->delete();

        return 'OK';
    }

    public function go_to_step(Project $project, $step) {
        $project->current_step = $step;
        $project->save();

        return route('qc-data-transformation', ['project' => $project->id]);

        if($step === 2)
            return redirect()->route('qc-data-transformation', ['project' => $project->id]);
    }




    public function createStList(Project $project) {

        //$project->createStList();
        RunScript::dispatch('Data import', $project, 'createStList', []);

        $project->current_step = 2;
        $project->save();

        return route('qc-data-transformation', ['project' => $project->id]);

    }


    public function searchGenes(Project $project) {
        $query = request('query');

        $context = (request()->has('context') && strlen(request('context'))) ? request('context') : 'initial';

        if(strlen($query)) {

            $genes = ProjectGene::where('project_id', $project->id)->where('context', $context)->where('gene', 'LIKE', $query . '%')->orderBy('gene')->limit(100);

            //$sql_with_bindings = Str::replaceArray('?', $genes->getBindings(), $genes->toSql());
            //dd($sql_with_bindings);

            return $genes->pluck('gene');

        }

        return [];
    }

    public function searchGenesRegexp(Project $project) {
        $query = request('query');

        $context = (request()->has('context') && strlen(request('context'))) ? request('context') : 'initial';

        if(strlen($query)) {
            $genes = ProjectGene::where('context', $context)->where('gene', 'REGEXP', $query)->orderBy('gene')->limit(100);

            //$sql_with_bindings = Str::replaceArray('?', $genes->getBindings(), $genes->toSql());
            //dd($sql_with_bindings);

            return $genes->pluck('gene');
        }

        return [];
    }


    public function applyFilter(Project $project) {

        RunScript::dispatch('Filter data', $project, 'applyFilter', request('parameters'));

        return 'OK';
        //return $project->applyFilter(request('parameters'));

    }

    public function generateFilterPlots(Project $project) {

        RunScript::dispatch('Generate filter plots', $project, 'generateFilterPlots', ['color_palette' => request('color_palette'), 'variable' => request('variable')]);

        //$project->generateFilterPlots(['color_palette' => request('color_palette'), 'variable' => request('variable')]);

        return response('OK');
    }

    public function applyNormalization(Project $project) {

        $project->current_step = 4;
        $project->save();

        RunScript::dispatch('Normalize data', $project, 'applyNormalization', request('parameters'));

        //return $project->applyNormalization(request('parameters'));

        return 'OK';

    }

    public function generateNormalizationPlots(Project $project) {

        RunScript::dispatch('Generate normalization plots', $project, 'generateNormalizationPlots', ['color_palette' => request('color_palette'), 'gene' => request('gene')]);

        //$project->generateNormalizationPlots(['color_palette' => request('color_palette'), 'gene' => request('gene')]);

        return response('OK');
    }

    public function applyPca(Project $project) {

        RunScript::dispatch('Principal Component Analysis', $project, 'applyPca', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'n_genes' => request('n_genes'), 'hm_display_genes' => request('hm_display_genes')]);

        //return $project->applyPca(['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'n_genes' => request('n_genes'), 'hm_display_genes' => request('hm_display_genes')]);

        return 'OK';

    }

    public function quiltPlot(Project $project) {

        RunScript::dispatch('Quilt plot', $project, 'quiltPlot', ['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'sample1' => request('sample1'), 'sample2' => request('sample2')]);

        //return $project->quiltPlot(['plot_meta' => request('plot_meta'), 'color_pal' => request('color_pal'), 'sample1' => request('sample1'), 'sample2' => request('sample2')]);

        return 'OK';

    }


    public function stplot_visualization(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.stplot-visualization')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function stplot_quilt(Project $project) {

        $genes = request('genes');
        $ptsize = request('ptsize');
        $col_pal = request('col_pal');
        $data_type = request('data_type');

        return $project->STplotQuilt($genes, $ptsize, $col_pal, $data_type);

    }

    public function stplot_expression_surface(Project $project) {

        $genes = request('genes');
        $ptsize = request('ptsize');
        $col_pal = request('col_pal');
        $data_type = request('data_type');

        return $project->STplotExpressionSurface($genes, $ptsize, $col_pal, $data_type);

    }

    public function sthet_spatial_het(Project $project) {
        $samples = $project->samples;
        $color_palettes = ColorPalette::orderBy('label')->get();
        return view('wizard.sthet-spatial-het')->with(compact('project', 'samples', 'color_palettes'));
    }

    public function sthet_spatial_het_plot(Project $project) {

        $genes = request('genes');
        $method = request('method');
        $color_pal = request('color_pal');
        $plot_meta = request('plot_meta');

        return $project->SThetPlot($genes, $method, $color_pal, $plot_meta);

    }


}
