<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ContactUs;
use Illuminate\View\View;

class HomeController extends Controller
{

    public function dashboard(): View
    {

        return view('dashboard');

    }

    public function contactUs() {

        try {

            $spatialGE = User::findOrFail(0);

            $spatialGE->notify(new ContactUs(request('subject'), request('description'), request('email')));

            return 'message sent';
        }
        catch(\Exception $e) {
            return $e->getMessage();
        }

    }


}
