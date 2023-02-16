<?php

namespace App\Http\Controllers;

use App\Mail\signUpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SecurityController extends Controller
{

    public function login() {

        return view('sign-in');

    }

    public function signin() {

        $credentials = request()->only('email', 'password');

        if (auth()->attempt($credentials)) {
            // Authentication passed...
            return response("Pass", 200); //return redirect()->intended(route('home'));
        }

        return response("Email and/or Password are incorrect!", 403);

    }

    public function signup() {

        return view('sign-up');

    }

    public function checkUserExists($email) {

        try {
            $user = User::where('email', $email)->firstOrFail();
            return 1;
        }
        catch (\Exception $e)
        {
            return 0;
        }

    }

    private function checkPasswordRules($password) {
        if(strlen($password) < 8)
            return false;

        //TODO: complete verifications


        return true;
    }

    public function create() {

        $email = request('email');
        $name = request('name');
        $password = request('password');

        if(!strlen(trim($email)) || !strlen(trim($name)) || !strlen(trim($password)))
            return response("You need to complete all the fields!", 400);

        if($this->checkUserExists($email))
            return response("You already have an account registered with this email", 400);

        if(!$this->checkPasswordRules($password)) {
            return response("Password should be at least 8 characters long!", 400);
        }

        $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);

        auth()->login($user);

        //Mail::to(['ramanjar@gmail.com'])->send(new signUpVerification());

        return redirect()->route('home');

    }

    public function destroy(): \Illuminate\Http\RedirectResponse
    {

        auth()->logout();

        return redirect()->route('home');
    }

}
