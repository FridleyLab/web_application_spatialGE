<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{

    public function dashboard(): View
    {

        return view('dashboard');

    }


}
