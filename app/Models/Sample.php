<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Sample extends Model
{
    use SoftDeletes;

    protected $table = 'samples';

    protected $fillable = ['name'];

    protected $appends = ['file_list', 'has_image', 'image_file_url'];



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

    public function getHasImageAttribute() {
        return $this->files()->where('type', 'imageFile')->count();
    }

    public function image_file_path() {
        try {
            $sample_folder = $this->projects[0]->workingDir() . $this->name . '/spatial/';
            $image = $this->files()->where('type', 'imageFile')->firstOrFail();
            $image_file = $sample_folder . $image->filename;
            $tissue_file = $sample_folder . 'image_' . $this->name . '.png';

            return Storage::fileExists($tissue_file) ? $tissue_file : $image_file;

        } catch (\Exception $e) {
            return '';
        }
    }

    public function getImageFileUrlAttribute() {
        return route('get-sample-image', ['sample' => $this->id]);
    }

}
