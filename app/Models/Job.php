<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    public $timestamps = false;

    protected $table = 'jobs';

    public function isRunning() {
        return $this->attempts && !is_null($this->reserved_at);
    }

    public function currentPosition() {
        return self::where('queue', $this->queue)
                ->where('available_at', '<', $this->available_at)
                ->whereNull('reserved_at')
                ->count() + 2;
    }

}
