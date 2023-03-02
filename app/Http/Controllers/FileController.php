<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\View\View;

class FileController extends Controller
{

    public function store() : string
    {

        if (request()->hasFile('file') && request()->file('file')->isValid()) {
            $_file = request()->file('file');
            $file = File::create(['filename' => $_file->getClientOriginalName()]);
            return $file->id;
        }
        else {
            return response('Something went wrong!', 500);
        }

    }


}
