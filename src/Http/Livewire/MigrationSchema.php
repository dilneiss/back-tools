<?php

namespace Backpack\DevTools\Http\Livewire;

use Illuminate\Support\ViewErrorBag;
use Livewire\Component;
use Str;

class MigrationSchema extends Component
{
    use Traits\InteractsWithApplication;
    use Traits\HasRelationTypes;
    use Traits\HasDatabaseCharsetAndCollation;
    use Traits\HasDatabaseModifiers;
    use Traits\HasDatabaseColumnTypes;

    public $field;

    public $columns = [];

    public $operation;

    public $current_disabled_column_types = [];

    public $creating_model_column = false;

    protected $listeners = [
        'removeColumnEvent',
        'addColumnForBelongsToRelation',
        'deleteRelationshipColumn',
        'changeColumnToBigInteger',
        'updateRelationshipKey',
        'updateRelationshipKeyByName',
        'belongsToRelationCreated',
        'updateModelList',
        'morphToRelationCreated',
        'createMorphToRelation',
        'addColumnForMorphToRelation',
        'relationshipRemoved',
        'updateMorphableName',
    ];

    public function hydrate()
    {
        $this->interactsWithApplicationInitialization();
    }

    public function mount($field, $operation)
    {
        $this->interactsWithApplicationInitialization(false); // bust the cache

        if (old('columns')) {
            $this->columns = old('columns');
            foreach ($this->columns as $column_index => $column) {
                $modifiers = $this->getColumnModifiers($column['column_type']);
                $this->columns[$column_index]['show_modifiers'] = $this->getModifierOpenState($column_index);
                $this->columns[$column_index]['column_type_modifiers'] = $modifiers;
                $this->columns[$column_index]['invalid_modifiers'] = [];
                $this->columns[$column_index]['has_relationship'] = $this->checkForRelationship($column['column_name'] ?? '');
                $this->columns[$column_index]['column_order'] = $column_index + 1;
                $this->columns[$column_index]['current_column_type'] = $column['column_type'];
                $this->columns[$column_index]['columns_for_table'] = isset($column['args']['foreign_table']) ? $this->getColumnsForTable($column['args']['foreign_table']) : [];

                $this->fetchColumnSpecificFields($column_index, true);
            }
        }

        if (count($this->columns) < 1) {
            $this->setupFirstRunColumns();
        }

        if (old('columns')) {
            session()->put('dvt_columns', $this->columns);
        }
    }

    private function getModifierOpenState($column_index)
    {
        $column = $this->columns[$column_index];
        if (old('columns')) {
            $errors = session()->get('errors', app(ViewErrorBag::class));

            foreach ($column['modifiers'] as $modifier => $value) {
                if ($errors->any() && $errors->getBag('default')->has('columns.'.$column_index.'.modifiers.'.$modifier)) {
                    return true;
                }
            }
        }

        return isset($column['show_modifiers']) ? (bool) $column['show_modifiers'] : false;
    }

    private function checkForRelationship($column_name)
    {
        if (old('relationships')) {
            $relationship = array_search($column_name, array_column(old('relationships'), 'relationship_column'));

            if (is_int($relationship)) {
                return $relationship;
            }
        }

        return false;
    }

    public function deleteRelationshipColumn($column_index)
    {
        $this->removeColumn($column_index);
    }

    public function addColumn()
    {
        $first_available_column = $this->getFirstAvailableColumn();

        $column_type = is_array($first_available_column) ? array_key_first($first_available_column) : $first_available_column;

        $modifiers = $this->getColumnModifiers($column_type);

        $this->columns[] = [
            'column_name' => '',
            'column_type' => $column_type,
            'column_order' => count($this->columns) + 1,
            'show_modifiers' => false,
            'column_type_modifiers' => $modifiers,
            'invalid_modifiers' => [],
            'has_relationship' => false,
            'columns_for_table' => [],
            'current_column_type' => $column_type,
        ];

        $column_index = count($this->columns) - 1;

        $this->fetchColumnSpecificFields($column_index, false, true);
    }

    public function updatedColumns($value, $updated_key)
    {
        $column_key = (int) Str::before($updated_key, '.');
        $column_name = Str::after($updated_key, '.');

        if ($column_name === 'column_name' && is_int($this->columns[$column_key]['has_relationship'])) {
            $this->dispatch(
                'updateRelationColumnName',
                column_index: $column_key,
                value: $value,
                is_model_selected: isset($this->columns[$column_key]['args']['model']) && ! empty($this->columns[$column_key]['args']['model']) ? true : false
            )->to(RelationshipSchema::class);

            $this->columns[$column_key]['args']['model'] ??= $this->getPossibleModelFromColumnName($value);

            if (! empty($this->columns[$column_key]['args']['model'])) {
                $this->inferModelValues($this->columns[$column_key]['args']['model'], $column_key);
                $this->dispatch(
                    'updateRelationshipModel',
                    relationship_index: $this->columns[$column_key]['has_relationship'],
                    model: $this->columns[$column_key]['args']['model']
                )->to(RelationshipSchema::class);
            }
        }

        if ($column_name === 'args.morphable' && is_int($this->columns[$column_key]['has_relationship'])) {
            $this->dispatch(
                'updateRelationColumnName',
                column_index: $column_key,
                value: $value
            )->to(RelationshipSchema::class);
        }

        if ($column_name == 'args.foreign_table') {
            $this->columns[$column_key]['columns_for_table'] = $this->getColumnsForTable($this->columns[$column_key]['args']['foreign_table']);
            $table_belongs_to_model = $this->tableBelongsToModel($value);

            if ($table_belongs_to_model) {
                if (! is_array($table_belongs_to_model)) {
                    $this->columns[$column_key]['args']['model'] = $table_belongs_to_model;
                } else {
                    $this->columns[$column_key]['args']['model'] = in_array($this->columns[$column_key]['args']['model'], $table_belongs_to_model) ? $this->columns[$column_key]['args']['model'] : $table_belongs_to_model[0];
                }
                $this->columns[$column_key]['args']['foreign_column'] = $this->getForeignColumnName($value, $this->columns[$column_key]['args']['model']);

                $this->dispatch(
                    'updateRelationshipModel',
                    relationship_index: $this->columns[$column_key]['has_relationship'],
                    model: $this->columns[$column_key]['args']['model']
                )->to(RelationshipSchema::class);
            } else {
                $this->columns[$column_key]['args']['model'] = '';
                $this->columns[$column_key]['args']['foreign_column'] = $this->getForeignColumnName($value);
            }
        }

        if ($column_name == 'modifiers.charset') {
            $this->columns[$column_key]['modifiers']['collation'] = '';
        }
    }

    public function relationshipRemoved($relationship_index)
    {
        collect($this->columns)->each(function ($column, $index) use ($relationship_index) {
            if (is_int($column['has_relationship']) && $column['has_relationship'] === $relationship_index) {
                $this->columns[$index]['has_relationship'] = false;
            }
        });
    }

    public function fetchModelInfo(int $column_index)
    {
        $column = $this->columns[$column_index];

        $this->dispatch(
            'updateRelationshipModel',
            relationship_index: $this->columns[$column_index]['has_relationship'],
            model: $column['args']['model']
        )->to(RelationshipSchema::class);

        if (! empty($column['args']['model'])) {
            $this->inferModelValues($column['args']['model'], $column_index);
        }
    }

    private function setupFirstRunColumns()
    {
        $default_columns = ['id', 'timestamps'];

        foreach ($default_columns as $column_type) {
            $modifiers = $this->getColumnModifiers($column_type);

            $this->columns[] = [
                'column_name' => $column_type == 'timestamps' ? '' : 'id',
                'column_type' => $column_type,
                'column_order' => count($this->columns) + 1,
                'show_modifiers' => false,
                'column_type_modifiers' => $modifiers,
                'invalid_modifiers' => [],
                'has_relationship' => false,
                'current_column_type' => $column_type,
                'columns_for_table' => [],
            ];
            $column_index = count($this->columns) - 1;

            $this->fetchColumnSpecificFields($column_index);
        }

        $last_column_index = count($this->columns) - 1;

        // trigger a browser event that can be caught with JS
        // to place the cursor inside the last added columns
        $this->dispatch('newColumnAdded', ['columnIndex' => $last_column_index]);
    }

    public function removeColumn(int $column_index)
    {
        unset($this->columns[$column_index]);
        //array values resets the array key to 0, 1, 2, 3 instead of 0, 1, 3 if you deleted a middle key.
        $this->columns = array_values($this->columns);

        $this->updateColumnOrder();
        $this->updateDisabledColumns();
    }

    public function removeColumnAndRelationship(int $column_index)
    {
        $column = $this->getColumnByIndex($column_index);

        if (is_int($column['has_relationship'])) {
            $this->dispatch('removeRelationshipEvent', $column['has_relationship'])->to(RelationshipSchema::class);
        }

        $this->removeColumn($column_index);
    }

    public function removeColumnEvent(int $column_index)
    {
        $this->removeColumn($column_index);
    }

    private function getColumnByIndex(int $index)
    {
        return $this->columns[$index];
    }

    private function updateColumnOrder()
    {
        foreach ($this->columns as $key => $column) {
            $this->columns[$key]['column_order'] = $key + 1;
        }
    }

    public function addColumnForBelongsToRelation(int $relationship_index, $column_name)
    {
        $modifiers = $this->getColumnModifiers('unsignedBigInteger');

        $this->columns[] = [
            'column_name' => $column_name,
            'column_type' => 'unsignedBigInteger',
            'column_order' => count($this->columns) + 1,
            'show_modifiers' => false,
            'column_type_modifiers' => $modifiers,
            'invalid_modifiers' => [],
            'has_relationship' => $relationship_index,
            'current_column_type' => 'unsignedBigInteger',
        ];
        $column_index = count($this->columns) - 1;

        $this->dispatch(
            'updateRelationColumnIndex',
            relationship_index: $relationship_index,
            column_index: $column_index
        )->to(RelationshipSchema::class);
        $this->dispatch(
            'updateRelationColumnName',
            column_index: $column_index,
            value: $column_name,
            is_model_selected: isset($this->columns[$column_index]['args']['model']) && ! empty($this->columns[$column_index]['args']['model']) ? true : false
        )->to(RelationshipSchema::class);

        $this->fetchColumnSpecificFields($column_index);
    }

    public function addColumnForMorphToRelation(int $relationship_index, $column_name)
    {
        $modifiers = $this->getColumnModifiers('morphs');

        $this->columns[] = [
            'column_name' => '',
            'column_type' => 'morphs',
            'column_order' => count($this->columns) + 1,
            'show_modifiers' => false,
            'column_type_modifiers' => $modifiers,
            'invalid_modifiers' => [],
            'has_relationship' => $relationship_index,
            'current_column_type' => 'morphs',
        ];
        $column_index = count($this->columns) - 1;

        $this->dispatch(
            'updateRelationColumnIndex',
            relationship_index: $relationship_index,
            column_index: $column_index
        )->to(RelationshipSchema::class);

        $this->dispatch(
            'updateRelationColumnName',
            column_index: $column_index,
            value: $column_name,
            is_model_selected: isset($this->columns[$column_index]['args']['model']) && ! empty($this->columns[$column_index]['args']['model']) ? true : false
        )->to(RelationshipSchema::class);

        $this->fetchColumnSpecificFields($column_index);

        $this->columns[$column_index]['args']['morphable'] = $column_name;
    }

    // we catch the focus event here and handle the case where the new model was created inside migration schema
    // otherwise we pass the event to be handled by relationship schema
    public function updateModelList()
    {
        $created_model = $this->updateModelListAndGetCreatedModel();

        if (! empty($created_model)) {
            if ($this->creating_model_column !== false) {
                $this->columns[$this->creating_model_column]['args']['model'] = reset($created_model);
                $this->dispatch(
                    'updateRelationshipModel',
                    relationship_index: $this->columns[$this->creating_model_column]['has_relationship'],
                    model: $this->columns[$this->creating_model_column]['args']['model'],
                )->to(RelationshipSchema::class);
                $this->creating_model_column = false;
            } else {
                $this->dispatch(
                    'updateRelationshipModelList',
                    created_model: $created_model
                )->to(RelationshipSchema::class);
            }
        }
    }

    public function creatingModelColumn($column_index)
    {
        $this->creating_model_column = $column_index;
    }

    public function updateRelationshipKey(int $column_index, int $relationship_index)
    {
        $previous_column = array_filter($this->columns, function ($column) use ($relationship_index) {
            return $column['has_relationship'] == $relationship_index;
        });

        if (! empty($previous_column)) {
            $this->columns[key($previous_column)]['has_relationship'] = false;
        }
        $this->columns[$column_index]['has_relationship'] = $relationship_index;
    }

    public function belongsToRelationCreated(int $column_index, string $model, int $relationship_index)
    {
        $this->updateRelationshipKey($column_index, $relationship_index);

        $this->columns[$column_index]['args']['model'] = in_array($model, $this->models->pluck('name')->toArray()) ? $model : '';

        if (! empty($this->columns[$column_index]['args']['model'])) {
            $this->inferModelValues($model, $column_index);
        }
    }

    public function morphToRelationCreated(int $column_index, int $relationship_index)
    {
        $this->updateRelationshipKey($column_index, $relationship_index);
    }

    public function updateRelationshipKeyByName($column_name, int $relationship_index)
    {
        $previous_column = array_filter($this->columns, function ($column) use ($relationship_index) {
            return is_int($column['has_relationship']) && $column['has_relationship'] === $relationship_index;
        });

        if (! empty($previous_column)) {
            $this->columns[key($previous_column)]['has_relationship'] = false;
        }
        if (! empty($column_name)) {
            $column = array_filter($this->columns, function ($column) use ($column_name) {
                return (isset($column['column_name']) && $column['column_name'] === $column_name) || (isset($column['args']['morphable']) && $column['args']['morphable'] === $column_name);
            });

            $this->columns[key($column)]['has_relationship'] = $relationship_index;
        }
    }

    public function updateMorphableName($morphable, int $relationship_index)
    {
        $column = array_filter($this->columns, function ($column) use ($relationship_index) {
            return is_int($column['has_relationship']) && $column['has_relationship'] === $relationship_index;
        });

        if (! empty($column)) {
            $this->columns[key($column)]['args']['morphable'] = $morphable;
        } else {
            $modifiers = $this->getColumnModifiers('morphs');

            $this->columns[] = [
                'column_name' => '',
                'column_type' => 'morphs',
                'column_order' => count($this->columns) + 1,
                'show_modifiers' => false,
                'column_type_modifiers' => $modifiers,
                'invalid_modifiers' => [],
                'has_relationship' => $relationship_index,
                'current_column_type' => 'morphs',
            ];

            $column_index = count($this->columns) - 1;

            $this->dispatch(
                'updateRelationColumnIndex',
                relationship_index: $relationship_index,
                column_index: $column_index
            )->to(RelationshipSchema::class);

            $this->dispatch(
                'updateRelationColumnName',
                column_index: $column_index,
                value: $morphable,
                is_model_selected: isset($this->columns[$column_index]['args']['model']) && ! empty($this->columns[$column_index]['args']['model']) ? true : false
            )->to(RelationshipSchema::class);

            $this->fetchColumnSpecificFields($column_index);

            $this->columns[$column_index]['args']['morphable'] = $morphable;
        }
    }

    private function setupModifierFields($column_index, $old = false)
    {
        $modifiers = $this->columns[$column_index]['column_type_modifiers'];

        //remove modifiers that will not be used when column change.
        if (isset($this->columns[$column_index]['modifiers'])) {
            foreach (array_keys($this->columns[$column_index]['modifiers']) as $cm) {
                if (! in_array($cm, $modifiers) || (! $old && ($this->column_modifiers[$cm]['type'] ?? '') == 'checkbox')) {
                    unset($this->columns[$column_index]['modifiers'][$cm]);
                }

                // if there are old columns, we run a "selectModifier" on the ones that are "checked"
                if (old('columns.'.$column_index.'.modifiers.'.$cm) && isset($this->columns[$column_index]['modifiers'][$cm]) && ($this->column_modifiers[$cm]['type'] ?? '') == 'checkbox') {
                    $this->columns[$column_index]['modifiers'][$cm] = $this->columns[$column_index]['modifiers'][$cm] == 'false' ? false : (bool) $this->columns[$column_index]['modifiers'][$cm];
                    $this->selectModifier($column_index, $cm);
                }
            }
        }

        foreach ($modifiers as $key => $mod_name) {
            if (! isset($this->columns[$column_index]['modifiers'][$mod_name])) {
                $modifier_definition = $this->column_modifiers[$mod_name];

                switch ($modifier_definition['type'] ?? '') {
                    case 'text':
                    case 'number':
                        $this->columns[$column_index]['modifiers'][$mod_name] = '';
                        break;
                    case 'checkbox':
                        if (isset($this->selectable_column_types[$this->columns[$column_index]['column_type']]) &&
                            isset($this->selectable_column_types[$this->columns[$column_index]['column_type']]['configs']) &&
                            isset($this->selectable_column_types[$this->columns[$column_index]['column_type']]['configs']['auto_modifiers'])) {
                            if (in_array($mod_name, $this->selectable_column_types[$this->columns[$column_index]['column_type']]['configs']['auto_modifiers'])) {
                                $this->columns[$column_index]['modifiers'][$mod_name] = true;
                            } else {
                                $this->columns[$column_index]['modifiers'][$mod_name] = false;
                            }
                        } else {
                            $this->columns[$column_index]['modifiers'][$mod_name] = false;
                        }
                        break;
                    case '':
                        $this->columns[$column_index]['modifiers'][$mod_name] = '';
                        break;
                }
            }
        }
    }

    public function fetchColumnSpecificFields(int $column_index, $old = false, $user_added = false)
    {
        $column_type = $this->columns[$column_index]['column_type'];
        $previous_column_type = $this->columns[$column_index]['current_column_type'];
        $auto_related_columns = array_map('strtolower', ['foreignId', 'belongsTo']);

        if (in_array(Str::lower($previous_column_type), $auto_related_columns) && ! in_array(Str::lower($column_type), $auto_related_columns)) {
            if (in_array(Str::lower($column_type), array_map('strtolower', $this->relation_types['BelongsTo']['available_column_types']))) {
                // if the new column type can hold the belongs to relation we don't delete it, we just "unforce" it.
                $this->dispatch(
                    'unforceRelation',
                    relationship_index: $this->columns[$column_index]['has_relationship']
                )->to(RelationshipSchema::class);
            } else {
                // in case the new column type can't hold the belongs to relation we completely remove the relationship
                $this->dispatch(
                    'removeRelationshipEvent',
                    relationship_index: $this->columns[$column_index]['has_relationship']
                )->to(RelationshipSchema::class);
            }
        }

        $this->columns[$column_index]['current_column_type'] = $column_type;
        $extras = $this->selectable_column_types[$column_type] ?? [];

        //reset all the column arguments
        if (! $old) {
            $this->columns[$column_index]['args'] = [];
        }

        foreach ($extras as $key => $configs) {
            if ($key !== 'configs') {
                if (isset($configs['force'])) {
                    $this->columns[$column_index]['args'][$key] = $configs['force'];
                } else {
                    if (! $old) {
                        $this->columns[$column_index]['args'][$key] = '';
                    }
                }
            }
        }

        $this->columns[$column_index]['column_type_modifiers'] = $this->getColumnModifiers($column_type);
        $this->columns[$column_index]['show_modifiers'] = $this->columns[$column_index]['show_modifiers'] ?? false;

        $this->updateDisabledColumns();

        $this->setupModifierFields($column_index, $old);

        if (! $old) {
            switch ($column_type) {
                case 'belongsTo':
                case 'foreignId': {
                    $this->createBelongsToRelation($this->columns[$column_index], $column_index);
                }
                    break;
                case 'timestamps':
                case 'timestampsTz': {
                    $current_order = $this->columns[$column_index]['column_order'];
                    while ($current_order != count($this->columns)) {
                        $this->moveColumnOrderDown($column_index);
                        $current_order++;
                    }
                    break;
                }
                default: {
                    $timestamps_column = array_filter($this->columns, function ($column) {
                        return in_array($column['column_type'], ['timestamps', 'timestampsTz']);
                    });

                    if (! empty($timestamps_column)) {
                        foreach ($timestamps_column as $timestamp_index => $column) {
                            if ($timestamp_index !== count($this->columns) - 1) {
                                $this->moveColumnOrderUp($column_index);
                                $column_index = $column_index - 1;
                            }
                        }
                    }
                    break;
                }
            }
        }

        // if column was added by the user we dispatch the event
        // to place the cursor inside this new column
        if ($user_added) {
            $this->dispatch('newColumnAdded', ['columnIndex' => $column_index]);
        }
    }

    private function createBelongsToRelation($column, $column_index)
    {
        $this->columns[$column_index]['args']['model'] = $this->getPossibleModelFromColumnName($this->columns[$column_index]['column_name']);
        $this->dispatch(
            'createBelongsToRelation',
            column: $column,
            column_index: $column_index
        )->to(RelationshipSchema::class);
    }

    private function createMorphToRelation($column, $column_index)
    {
        $this->dispatch(
            'createMorphToRelation',
            column_name: $column,
            column_index: $column_index
        )->to(RelationshipSchema::class);
    }

    public function createMorphToRelationFromColumn($column_index)
    {
        $relation_name = $this->columns[$column_index]['args']['morphable'];
        if (! empty($relation_name)) {
            $this->dispatch(
                'createMorphToRelation',
                column_name: $relation_name,
                column_index: $column_index
            )->to(RelationshipSchema::class);
        }
    }

    public function showModifiers(int $column_index)
    {
        $this->columns[$column_index]['show_modifiers'] = true;
    }

    public function hideModifiers(int $column_index)
    {
        $this->columns[$column_index]['show_modifiers'] = false;
    }

    public function moveColumnOrderUp(int $column_index)
    {
        $current_order = $this->columns[$column_index]['column_order'];
        $desired_order_key = array_search($current_order - 1, array_column($this->columns, 'column_order'));

        if (is_int($this->columns[$column_index]['has_relationship'])) {
            $this->dispatch(
                'updateRelationColumnIndex',
                relationship_index: $this->columns[$column_index]['has_relationship'],
                column_index: $column_index - 1
            )->to(RelationshipSchema::class);
        }

        if (is_int($this->columns[$desired_order_key]['has_relationship'])) {
            $this->dispatch(
                'updateRelationColumnIndex',
                relationship_index: $this->columns[$desired_order_key]['has_relationship'],
                column_index: $desired_order_key + 1
            )->to(RelationshipSchema::class);
        }

        $this->columns[$desired_order_key]['column_order'] = $current_order;
        $this->columns[$column_index]['column_order'] = $current_order - 1;
    }

    public function moveColumnOrderDown(int $column_index)
    {
        $current_order = $this->columns[$column_index]['column_order'];
        $desired_order_key = array_search($current_order + 1, array_column($this->columns, 'column_order'));

        if (is_int($this->columns[$column_index]['has_relationship'])) {
            $this->dispatch(
                'updateRelationColumnIndex',
                relationship_index: $this->columns[$column_index]['has_relationship'],
                column_index: $column_index + 1
            )->to(RelationshipSchema::class);
        }

        if (is_int($this->columns[$desired_order_key]['has_relationship'])) {
            $this->dispatch(
                'updateRelationColumnIndex',
                relationship_index: $this->columns[$desired_order_key]['has_relationship'],
                column_index: $desired_order_key - 1
            )->to(RelationshipSchema::class);
        }

        $this->columns[$desired_order_key]['column_order'] = $current_order;
        $this->columns[$column_index]['column_order'] = $current_order + 1;
    }

    public function selectModifier(int $column_index, $modifier)
    {
        $modifier_definition = $this->column_modifiers[$modifier];
        $invalidates_on_select = $modifier_definition['invalidates_on_select'] ?? [];
        if ($this->columns[$column_index]['modifiers'][$modifier]) {
            foreach ($invalidates_on_select as $md_) {
                array_push($this->columns[$column_index]['invalid_modifiers'], $md_);
                $this->columns[$column_index]['modifiers'][$md_] = ($this->column_modifiers[$md_]['type'] ?? '') == 'checkbox' ? false : '';
            }
        } else {
            $this->columns[$column_index]['invalid_modifiers'] = array_diff($this->columns[$column_index]['invalid_modifiers'], $invalidates_on_select);
        }
    }

    public function isColumnTypeEnabled($column_type, $column)
    {
        return $column_type !== '-' && (
            $column_type === $column['column_type'] ||
            in_array($column_type, $this->selectable_column_types[$column['column_type']]['configs']['disables'] ?? []) ||
            ! in_array($column_type, $this->current_disabled_column_types)
        );
    }

    private function updateDisabledColumns()
    {
        $this->current_disabled_column_types = [];
        foreach ($this->columns as $column) {
            $disabled = $this->selectable_column_types[$column['column_type']]['configs']['disables'] ?? [];
            $this->current_disabled_column_types = array_unique(array_merge($this->current_disabled_column_types, $disabled));
        }
    }

    private function getColumnModifiers($column_type)
    {
        $modifiers = [];
        foreach ($this->column_modifiers as $modifier_name => $modifier) {
            if (! isset($modifier['valid_for']) || in_array($column_type, $modifier['valid_for'])) {
                if (! isset($modifier['invalid_for']) || ! in_array($column_type, $modifier['invalid_for'])) {
                    array_push($modifiers, $modifier_name);
                }
            }
        }

        return $modifiers;
    }

    private function getFirstAvailableColumn()
    {
        $compare = [];
        array_map(function ($arr) use (&$compare) {
            return $compare[$arr] = $arr;
        }, $this->current_disabled_column_types);

        $available_columns = array_diff_key($this->selectable_column_types, $compare);

        return array_key_first($available_columns);
    }

    public function render()
    {
        usort($this->columns, function ($a, $b) {
            return ($a['column_order'] < $b['column_order']) ? -1 : 1;
        });

        $this->dispatch(
            'updateMigrationSchemaColumns',
            columns: $this->columns
        )->to(RelationshipSchema::class);

        return view('backpack.devtools::livewire.migration-schema');
    }

    // this triggers a browser event that will ask for user confirmation.
    // after evaluating the user decision it runs or not the callback.
    public function confirmColumnDelete($callback, ...$argv)
    {
        $this->dispatch('confirmColumnDelete', compact('callback', 'argv'));
    }
}
