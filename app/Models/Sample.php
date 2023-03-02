<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sample extends Model
{
    use SoftDeletes;

    protected $table = 'samples';

    protected $fillable = ['name'];

    protected $appends = ['file_list'];



    //Relations
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class);
    }

    public function getFileListAttribute() {
        return $this->files;
    }

}
