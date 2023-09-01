<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectGene extends Model
{
    public $timestamps = false;

    protected $table = 'project_genes';

    //protected $fillable = ['gene', 'project_id'];

    //Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /*public function genes(): BelongsToMany
    {
        return $this->belongsToMany(Gene::class, 'project_gene');
    }*/


}
