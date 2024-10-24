<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $table = 'files';

    protected $fillable = ['filename', 'type'];

    protected $appends = ['extension'];

    //Relations
    public function samples(): BelongsToMany
    {
        return $this->belongsToMany(Sample::class);
    }

    public function getExtensionAttribute() {

        $parts = explode('.', $this->filename);

        return $parts[count($parts)-1];

    }


}
