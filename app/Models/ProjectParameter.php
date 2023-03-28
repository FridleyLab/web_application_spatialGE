<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectParameter extends Model
{
    use SoftDeletes;

    protected $table = 'project_parameters';

    protected $fillable = ['parameter', 'type', 'value', 'project_id'];

    //Relations
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }


}
