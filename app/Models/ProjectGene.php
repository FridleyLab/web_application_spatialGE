<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectGene extends Model
{
    use SoftDeletes;

    protected $table = 'project_genes';

    protected $fillable = ['gene', 'project_id'];

    //Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }


}
