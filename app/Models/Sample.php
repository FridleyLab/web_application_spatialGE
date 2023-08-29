<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Sample extends Model
{
    use SoftDeletes;

    protected $table = 'samples';

    protected $fillable = ['name'];

    protected $appends = ['file_list', 'has_image', 'image_file_url', 'expression_file', 'coordinates_file', 'image_file'];



    //Relations
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class);
    }


    private function isTestUser() {
        $userName = $this->projects[0]->user->name;
        return (str_contains($userName, '_test_user_') && str_contains($userName, '@moffitt.org'));
    }

    public function getExpressionFileAttribute() {
        if($this->isTestUser()) {
            $file = new File;
            $file->filename = 'test.h5';
            return $file;
        }

        return $this->files()->where('type', 'expressionFile')->firstOrFail();
    }

    public function getCoordinatesFileAttribute() {

        if($this->isTestUser()) return null;

        return $this->files()->where('type', 'coordinatesFile')->firstOrFail();
    }

    public function getImageFileAttribute() {
        $images = $this->files()->where('type', 'imageFile')->get();
        return $images->count() ? $images[0] : null;
    }

    public function getFileListAttribute() {
        return $this->files;
    }

    public function getHasImageAttribute() : int {
        return $this->files()->where('type', 'imageFile')->count();
    }

    public function image_file_path($relative_path = false) {
        try {

            $sample_folder = $this->projects[0]->workingDir() . $this->name . '/spatial/';
            $path_to_return = ($relative_path ? '' : $this->projects[0]->workingDir()) . $this->name . '/spatial/';

            $image = $this->files()->where('type', 'imageFile')->firstOrFail();

            $tissue_filename = 'image_' . $this->name . '.png';

            //$image_file = $sample_folder . $image->filename;
            $tissue_file = $sample_folder . $tissue_filename;

            return $path_to_return . (Storage::fileExists($tissue_file) ? $tissue_filename : $image->filename);

        } catch (\Exception $e) {
            return '';
        }
    }

    public function getImageFileUrlAttribute() {
        return route('get-sample-image', ['sample' => $this->id]);
    }

}
