<?php

namespace App\Http\Controllers;

use App\Models\User;

class SecurityController extends Controller
{

    public function create() {

        //Fake log in (for developing/testing purposes)
        return '<a href="/test-login">Get in</a>';

        //TODO: implment log in logic here

        return redirect()->intended('/');

    }

    public function destroy() {

        auth()->logout();

        return redirect()->route('login');
    }


    public function testLogIn() {
        if(!auth()->check())
            auth()->login(User::findOrFail(1));

        return redirect()->route('home');
    }

}
