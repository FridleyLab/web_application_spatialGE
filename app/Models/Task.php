<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{

    protected $table = 'tasks';

    public $timestamps = false;

    protected $fillable = ['task', 'project_id', 'process', 'user_id', 'scheduled_at', 'started_at', 'finished_at'];

    //Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }


}
