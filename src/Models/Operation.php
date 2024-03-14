<?php

namespace Backpack\DevTools\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\DevTools\CustomFile;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Sushi\Sushi;

class Operation extends EloquentModel
{
    use CrudTrait, Sushi;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'operations';

    // define a dummy schema for when the Sushi model has no entries
    // (when the searched directories are all empty)
    protected $schema = [
        'id' => 'integer',
    ];

    protected $guarded = ['id'];

    protected $dates = [
        'file_created_at',
        'file_last_accessed_at',
        'file_last_changed_at',
        'file_last_modified_at',
    ];

    public static function setSushiConnection($connection): void
    {
        static::$sushiConnection = $connection;
    }

    /**
     * Instead of looking inside the database for results, this method is called
     * by Sushi to provide all rows for this Eloquent model (the Model - model).
     *
     * @return array
     */
    public function getRows(): array
    {
        $paths = config('backpack.devtools.paths.operations');

        return CustomFile::allFrom($paths)
            ->filter(function ($file) {
                return $file->isPhp() && $file->isOperation();
            })
            ->values()
            ->toArray();
    }

    public function tableExists(): bool
    {
        return Schema::hasTable($this->getTableAttribute());
    }

    /**
     * Get the name of that model's DB table.
     *
     * @return string the string specified as table name inside the model file
     */
    public function getTableAttribute(): string
    {
        return $this->instance->getTable();
    }

    public function getRelatedFiles(): array
    {
        return [
            'operation' => $this->file_path ? new CustomFile($this->file_path) : '',
            'crud_controller' => $this->crud_controller_files,
        ];
    }

    public function getCrudControllerFilesAttribute(): array
    {
        $classWithNamespace = $this->class_path;
        $paths = config('backpack.devtools.paths.crud_controllers', []);

        return CustomFile::allFrom($paths)
            ->filter(function ($file) {
                return $file->isPhp() && $file->isCrudController();
            })
            ->filter(function ($file) use ($classWithNamespace) {
                return Str::contains($file->file_contents, 'use \\'.$classWithNamespace.';');
            })
            ->all();
    }

    public function getRelatedViewFilesAttribute(): array
    {
        $operationName = lcfirst(str_replace('Operation', '', $this->file_name));
        $files = [];

        // Button
        $buttonPath = resource_path('views/vendor/backpack/crud/buttons/').$operationName.'.blade.php';
        if (File::exists($buttonPath)) {
            $files[] = new CustomFile($buttonPath);
        }

        // Custom view
        $customViewPath = resource_path('views/vendor/backpack/crud/operations/').$operationName.'.blade.php';
        if (File::exists($customViewPath)) {
            $files[] = new CustomFile($customViewPath);
        }

        return $files;
    }

    /**
     * Check if the database table exists in the DBMS.
     */
    public function getTableExistsAttribute(): bool
    {
        return $this->tableExists();
    }

    /**
     * Get a model instance.
     */
    public function getInstanceAttribute(): EloquentModel
    {
        return app($this->class_path);
    }

    /**
     * Get the clear name of the model (no prefix, no extension, nothing).
     */
    public function getNameAttribute(): string
    {
        return $this->file_name;
    }

    /**
     * Get the title of the model.
     */
    public function getTitleAttribute(): string
    {
        return Str::of($this->file_name)->substr(18)->replace('_', ' ')->ucfirst();
    }
}
