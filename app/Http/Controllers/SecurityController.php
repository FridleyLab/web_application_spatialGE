<?php

namespace App\Http\Controllers;

use App\Mail\userAccountActivation;
use App\Models\ProfileIndustry;
use App\Models\ProfileInterest;
use App\Models\ProfileJob;
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

        session()->invalidate();

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

        $industries = ProfileIndustry::all()->pluck('name');
        $jobs = ProfileJob::all()->pluck('name');
        $areas_of_interest = ProfileInterest::all()->pluck('name');

        return view('sign-up')->with(compact('industries', 'jobs', 'areas_of_interest'));

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

        $interest = request('interest');
        $job = request('job');
        $industry = request('industry');

        if(!strlen(trim($email)) || !strlen(trim($first_name)) || !strlen(trim($last_name)) || !strlen(trim($password)) || !strlen(trim($industry)) || !strlen(trim($job)) || !strlen(trim($interest)))
            return response('You need to complete all the fields!', 400);

        if($this->checkUserExists($email))
            return response('There\'s already an account registered with this email', 400);
            //return response()->view('show-message', ['message' => 'There\'s already an account registered with this email'], 400);

        if(!$this->checkPasswordRules($password)) {
            return response('Password should be at least 8 characters long!', 400);
        }

        try {
            $user = User::create(['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'email_verification_code' => '', 'password' => Hash::make($password), 'industry' => $industry, 'interest' => $interest, 'job' => $job]);
            //create hashed value based on the user's email to use as activation code
            $user->email_verification_code = $user->id . '__||__' . str_replace('/', '', Hash::make($user->email));
            $user->save();
            Mail::to($email)->send(new userAccountActivation($user));

            //TODO: configure to only execute in a local environment
            return response(route('account-activation-dev', ['user' => $user->id]), 200);
            //return response('<p>Account created, please check your email to activate your account.</p><p>Please activate your account <a href="' . route('account-activation', ['code' => $user->email_verification_code]) . '">here</a></p>', 200);
        }
        catch(\Exception $e) {
            return response('Something went wrong!. ' . $e->getMessage(), 400);
        }

        //return redirect()->route('home');

    }

    public function activateDev(User $user) {
        return view('mail.user_account_activation_dev')->with(['user' => $user]);
    }

    public function activate($code) {

        try {
            $user = User::where('email_verification_code', $code)->firstOrFail();
            $link = '<a href="' . route('login') . '">Sign in</a>';
            if(is_null($user->email_verified_at)) {
                $user->email_verified_at = Carbon::now();
                $user->save();
                return response()->view('show-message', ['message' => 'Your account has been activated. Now you can ' . $link], 200);
            }
            else { return response()->view('show-message', ['message' => 'Your account had already been activated!' . $link], 400); }
        }
        catch (\Exception $e) {
            return response()->view('show-message', ['message' => 'Something went wrong!'], 400);
        }
    }

    public function destroy(): \Illuminate\Http\RedirectResponse
    {
        session()->invalidate();

        auth()->logout();

        return redirect()->route('home');
    }

    public function sendPasswordRecoveryEmail() {

        $email = request('email');

        try {
            $user = User::where('email', $email)->firstOrFail();

            //TODO: send email

        }
        catch (\Exception $e) {
            return response('Email address nor found!', 400);
        }
    }

    public function sendPasswordRecoveryEmailDev() {

        $email = request('email');

        try {
            $user = User::where('email', $email)->firstOrFail();
            return view('mail.user_account_password_reset_dev')->with(['user' => $user]);
        }
        catch(\Exception $e) {
            return response('Something went wrong! ' . $email, 500);
        }


    }

    public function resetPasswordForm($code) {
        try {
            $user = User::where('email_verification_code', $code)->firstOrFail();
            return view('sign-in-password-reset')->with(['user' => $user]);
        }
        catch(\Exception $e) {
            return response('Something went wrong!', 500);
        }
    }

}
