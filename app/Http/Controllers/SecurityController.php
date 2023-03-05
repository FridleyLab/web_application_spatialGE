<?php

namespace App\Http\Controllers;

use App\Mail\userAccountActivation;
use App\Models\User;
use Carbon\Carbon;
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

            //Check if account has been activated
            if(is_null(auth()->user()->email_verified_at)) {
                auth()->logout();
                return response("<p>You have not activated your account yet</p><p>Please check your email</p>", 401);
            }

            return response("Pass", 200); //return redirect()->intended(route('home'));
        }

        return response("Email/Password incorrect!", 403);

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
        $first_name = request('first_name');
        $last_name = request('last_name');
        $password = request('password');

        if(!strlen(trim($email)) || !strlen(trim($first_name)) || !strlen(trim($last_name)) || !strlen(trim($password)))
            return response('You need to complete all the fields!', 400);

        if($this->checkUserExists($email))
            return response('There\'s already an account registered with this email', 400);
            //return response()->view('show-message', ['message' => 'There\'s already an account registered with this email'], 400);

        if(!$this->checkPasswordRules($password)) {
            return response('Password should be at least 8 characters long!', 400);
        }

        try {
            $user = User::create(['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'email_verification_code' => '', 'password' => Hash::make($password)]);
            //create hashed value based on the user's email to use as activation code
            $user->email_verification_code = $user->id . '__||__' . str_replace('/', '', Hash::make($user->email));
            $user->save();
            Mail::to($email)->send(new userAccountActivation($user));

            return response('<p>Account created, please check your email to activate your account.</p><p>Please activate your account <a href="' . route('account-activation', ['code' => $user->email_verification_code]) . '">here</a></p>', 200);
        }
        catch(\Exception $e) {
            return response('Something went wrong!. ' . $e->getMessage(), 400);
        }



        //return redirect()->route('home');

    }

    public function activate($code) {

        try {
            $user = User::where('email_verification_code', $code)->firstOrFail();

            if(is_null($user->email_verified_at)) {
                $user->email_verified_at = Carbon::now();
                $user->save();
                return response()->view('show-message', ['message' => 'Your account has been activated. Now you can sign in'], 200);
            }
            else { return response()->view('show-message', ['message' => 'Your account had already been activated!'], 400); }
        }
        catch (\Exception $e) {
            return response()->view('show-message', ['message' => 'Something went wrong!'], 400);
        }
    }

    public function destroy(): \Illuminate\Http\RedirectResponse
    {

        auth()->logout();

        return redirect()->route('home');
    }

}
