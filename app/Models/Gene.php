<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gene extends Model
{
    public $timestamps = false;

    protected $table = 'genes';

    protected $fillable = ['name'];

}
