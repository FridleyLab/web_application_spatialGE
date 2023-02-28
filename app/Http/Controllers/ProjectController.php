<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ProjectController extends Controller
{

    public function create() {

        $name = request('name');
        $description = request('description');

    }


    public function wizard(): View
    {

        return view('wizard.wizard');

    }




}
