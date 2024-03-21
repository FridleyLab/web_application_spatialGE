<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProcessFiles extends Model
{
    protected $table = 'project_process_files';

    protected $fillable = ['project_id', 'process', 'files'];

    //Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }


}
