<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSample extends Model
{
    use SoftDeletes;

    protected $table = 'project_sample';
    
}
