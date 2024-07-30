<?php

namespace Backpack\DevTools\Http\Livewire\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait InteractsWithApplication
{
    public $schema;

    public $models;

    private $previous_models;

    public $table_list;

    /**
     * @var \Backpack\CRUD\app\Library\Database\DatabaseSchema
     */
    private $schema_manager;

    public function interactsWithApplicationInitialization(bool $cached = true)
    {
        $this->schema_manager = app('DatabaseSchema');

        $this->buildSchemaAndModels($cached);
    }

    private function buildSchemaAndModels(bool $cached)
    {
        if (! $cached) {
            Cache::forget('devToolsDbSchema');
            Cache::forget('devToolsModelList');
        }

        $this->schema = Cache::rememberForever('devToolsDbSchema', function () {
            return $this->getDbSchema();
        });

        $this->buildModels();

        $this->table_list = $this->createTableList();
    }

    private function buildModels()
    {
        $this->previous_models = $this->models;
        $this->models = Cache::rememberForever('devToolsModelList', function () {
            return $this->getModelList();
        });
    }

    private function getModelList()
    {
        return \Backpack\DevTools\Models\Model::orderBy('class_path')->pluck('class_path')->map(function ($item) {
            $model_fqn = '\\'.$item;
            $model_instance = (new $model_fqn);

            return [
                'name' => $item,
                'table' => $model_instance->getTable(),
                'has_index' => $this->checkModelTableForIndex($model_instance),
            ];
        });
    }

    private function checkModelTableForIndex($model)
    {
        return ! empty($this->schema_manager->listTableIndexes(connection: $model->getConnection()->getName(), table: $model->getTable()));
    }

    private function getDbSchema()
    {
        return collect($this->schema_manager->getTables())->map(function ($table, $key) {
            $indexes = $this->schema_manager->listTableIndexes(connection: config('database.default'), table: $table->getName());
            $columns = collect($table->getColumns())
            ->map(function ($column, $key) use ($indexes) {
                return [
                    'type' => $column->getType()->getName(),
                    'unsigned' => $column->getUnsigned(),
                    'is_index' => in_array($column->getName(), $indexes),
                ];
            })->toArray();

            return [
              'name' => $table->getName(),
              'columns' => $columns,
              'has_index' => ! empty($indexes),
              'table_indexes' => $indexes,
            ];
        })->toArray();
    }

    private function createTableList()
    {
        return array_keys($this->schema);
    }

    private function getPossibleModelFromColumnName($column_name)
    {
        $possible_model = $this->models->filter(function ($model) use ($column_name) {
            return Str::afterLast($model['name'], '\\') === Str::ucfirst(Str::beforeLast($column_name, '_id'));
        })->first();

        return ! empty($possible_model) ? $possible_model['name'] : '';
    }

    private function tableBelongsToModel($table)
    {
        $model_tables = $this->models->where('table', $table);

        return $model_tables->count() > 1 ? $model_tables->pluck('name')->toArray() : ($model_tables->isEmpty() ? false : $model_tables->first()['name']);
    }

    private function getColumnsForTable($table)
    {
        return $this->schema_manager->listTableColumnsNames(connection: config('database.default'), table: $table);
    }

    private function inferModelValues(string $model_class, int $column_index)
    {
        $model = new $model_class();
        $this->columns[$column_index]['args']['foreign_table'] = $this->modelTableExistsInSchema($model) ? $model->getTable() : '';
        $this->columns[$column_index]['columns_for_table'] = $this->getColumnsForTable($model->getTable());
        $this->columns[$column_index]['args']['foreign_column'] = $this->inferForeignColumn($model);
    }

    private function inferForeignColumn($model)
    {
        if ($this->checkModelTableForIndex($model)) {
            return $this->modelKeyIsIndex($model) ? $model->getKeyName() : $this->getFirstIndexColumn($model);
        }

        return '';
    }

    private function modelTableExistsInSchema($model)
    {
        return in_array($model->getTable(), $this->table_list);
    }

    private function modelKeyIsIndex($model)
    {
        $table_name = $model->getTableWithPrefix();

        return in_array($model->getKeyName(), $this->schema_manager->listTableIndexes(table: $table_name, connection: $model->getConnection()->getName()));
    }

    private function getFirstIndexColumn($model)
    {
        $table_name = $model->getTableWithPrefix();

        return current($this->schema_manager->listTableIndexes(table: $table_name, connection: $model->getConnection()->getName()));
    }

    private function getRelationNameFromColumnName($relation)
    {
        if (is_array($relation)) {
            if (! isset($relation['relationship_column']) || ! $relation['relationship_column']) {
                return false;
            }

            return Str::camel(Str::beforeLast($relation['relationship_column'], '_id'));
        }

        return Str::camel(Str::beforeLast($relation, '_id'));
    }

    private function getForeignColumnName(string $table_name, $model = null)
    {
        $model = is_string($model) ? new $model() : $model;

        $connection = $model?->getConnection()->getName() ?? null;
        $indexes = $this->schema_manager->listTableIndexes(table: $table_name, connection: $connection);

        if (empty($indexes)) {
            return '';
        }

        if (is_string($model)) {
            $model_instance = (new $model);
            if ($model_instance instanceof \Illuminate\Database\Eloquent\Model || is_subclass_of($model_instance, \Illuminate\Database\Eloquent\Model::class)) {
                if (in_array($model_instance->getKeyName(), $indexes)) {
                    return $model_instance->getKeyName();
                }
            }
        }

        return current($indexes);
    }

    private function updateModelListAndGetCreatedModel()
    {
        $this->buildModels();
        if (! is_null($this->previous_models)) {
            return array_diff(array_column($this->models->toArray(), 'name'), array_column($this->previous_models->toArray(), 'name'));
        }
    }

    private function inferMorphableFromModel($model)
    {
        $file_path = (new \ReflectionClass($model))->getFileName();

        $model_contents = Str::of(File::get($file_path))
                            ->explode("\n");

        $possible_morphable = '';

        $model_contents->filter(function ($line) use (&$possible_morphable) {
            return Str::of($line)->contains('morphTo');
        })->each(function ($content, $line) use ($model_contents, &$possible_morphable) {
            $function_line = Str::of($model_contents[$line]);
            $index = 1;
            while (! $function_line->contains('function')) {
                $function_line = Str::of($model_contents[$line - $index]);
                $index++;
            }

            $possible_morphable = $function_line->before('(')->afterLast(' ')->__toString();
        });

        return $possible_morphable;
    }

    /**
     * Check if relationship with given name is auto-generated by migration column.
     *
     * @return bool
     */
    private function isCreatedByColumn(string $column_name)
    {
        $column = $this->getcolumnIndexFromName($column_name);

        return in_array(Str::lower($this->migration_schema_columns[$column]['column_type']), ['belongsto', 'foreignid']);
    }

    private function getModelNameFromColumnName($column_name)
    {
        return 'App\\Models\\'.ucwords(Str::camel(Str::beforeLast($column_name, '_id')));
    }
}
