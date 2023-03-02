<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = ['name', 'description', 'user_id'];

    protected $appends = ['url'];


    //Relations
    public function samples(): BelongsToMany
    {
        return $this->belongsToMany(Sample::class);
    }


    //Attributes
    public function getUrlAttribute() {
        return route('import-data', ['project' => $this->id]);
    }

}
