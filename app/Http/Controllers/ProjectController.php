<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ProjectController extends Controller
{

    public function wizard(): View
    {

        return view('wizard.wizard');

    }


}
