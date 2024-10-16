<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $appends = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'email_verification_code',
        'industry',
        'job',
        'interest',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }


    public function getNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getUserFolder() : string {

        Storage::createDirectory('users');
        $userFolder = 'users/' . $this->id . '/';
        Storage::createDirectory($userFolder);

        return $userFolder;

    }

    public function hasDemoProject() {

        foreach($this->projects as $project) {
            if($project->isDemoProject()) {
                return true;
            }
        }

        return false;
    }

    public function getDemoProject() {

        foreach($this->projects as $project) {
            if($project->isDemoProject()) {
                return $project;
            }
        }

        return false;
    }

}
