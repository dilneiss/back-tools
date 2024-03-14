<?php

namespace Backpack\DevTools\Http\Livewire\Traits;

trait HasDatabaseColumnTypes
{
    public $selectable_column_types = [
        'string' => [
            'size' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 255,
                    'min' => 0,
                    'max' => 4294967295,
                ],
            ],
        ],
        'bigIncrements' => [
            'configs' => [
                'auto_modifiers' => ['autoIncrement', 'unsigned'],
                'disables' => [
                    'id', 'smallIncrements', 'mediumIncrements', 'tinyIncrements', 'increments', 'bigIncrements',
                ],
            ],
        ],
        'bigInteger' => [],
        'binary' => [],
        'boolean' => [],
        'belongsTo' => [],
        'char' => [
            'size' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 1,
                    'min' => 0,
                    'max' => 255,
                ],
            ],
        ],
        'dateTimeTz' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 2,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
        ],
        'dateTime' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 2,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
        ],
        'date' => [],
        'decimal' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 4,
                    'min' => 1,
                    'max' => 65,
                ],
            ],
            'scale' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 2,
                    'min' => 0,
                    'max' => 30,
                ],
            ],
        ],
        'double' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 4,
                    'min' => 0,
                    'max' => 255,
                ],
            ],
            'scale' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 2,
                    'min' => 0,
                    'max' => 30,
                ],
            ],
        ],
        'enum' => [
            'values' => [
                'type' => 'enumerable',
                'attributes' => [
                    'placeholder' => 'first, second, third',
                    'required' => 'required',
                ],
            ],
        ],
        'float' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 4,
                    'min' => 0,
                    'max' => 255,
                ],
            ],
            'scale' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 2,
                    'min' => 0,
                    'max' => 30,
                ],
            ],
        ],
        'foreignId' => [],
        'geometryCollection' => [],
        'geometry' => [],
        'id' => [
            'configs' => [
                'auto_modifiers' => ['autoIncrement', 'unsigned'],
                'disables' => ['increments', 'smallIncrements', 'mediumIncrements', 'tinyIncrements', 'bigIncrements', 'id'],
            ],
        ],
        'increments' => [
            'configs' => [
                'auto_modifiers' => ['unsigned', 'autoIncrement'],
                'disables' => ['id', 'smallIncrements', 'mediumIncrements', 'tinyIncrements', 'bigIncrements', 'increments'],
            ],
        ],
        'integer' => [],
        'ipAddress' => [],
        'json' => [],
        'jsonb' => [],
        'lineString' => [],
        'longText' => [],
        'macAddress' => [],
        'mediumIncrements' => [
            'configs' => [
                'auto_modifiers' => ['autoIncrement', 'unsigned'],
                'disables' => ['id', 'smallIncrements', 'increments', 'tinyIncrements', 'bigIncrements', 'mediumIncrements'],
            ],
        ],
        'mediumInteger' => [],
        'mediumText' => [],
        'morphs' => [
            'configs' => [
                'placeholder' => 'morphable_id & morphable_type',
            ],
        ],
        'multiLineString' => [],
        'multiPoint' => [],
        'multiPolygon' => [],
        'nullableTimestamps' => [
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'created_at & updated_at',
            ],
            'disables' => [
                'nullableTimestamps', 'timestamps', 'timestampsTz',
            ],
        ],
        'nullableMorphs' => [
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'morphable_id & morphable_type',
            ],
        ],
        'nullableUuidMorphs' => [
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'morphable_id & morphable_type',
            ],
        ],
        'point' => [],
        'polygon' => [],
        'rememberToken' => [
            'size' => [
                'type' => 'number',
                'force' => 100,
            ],
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'remember_token',
            ],
        ],
        'set' => [
            'values' => [
                'type' => 'enumerable',
                'attributes' => [
                    'placeholder' => 'first, second, third',
                    'required' => 'required',
                ],
            ],
        ],
        'smallIncrements' => [
            'configs' => [
                'auto_modifiers' => ['autoIncrement', 'unsigned'],
                'disables' => ['id', 'increments', 'mediumIncrements', 'tinyIncrements', 'bigIncrements', 'smallIncrements'],
            ],
        ],
        'smallInteger' => [],
        'softDeletesTz' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
            'configs' => [
                'placeholder' => 'deleted_at',
                'auto_modifiers' => ['nullable'],
            ],
        ],
        'softDeletes' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'deleted_at',
            ],
        ],
        'text' => [],
        'timeTz' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
        ],
        'time' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
        ],
        'timestampTz' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
        ],
        'timestamp' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
        ],
        'timestampsTz' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'created_at & updated_at',
                'disables' => [
                    'nullableTimestamps', 'timestamps', 'timestampsTz',
                ],
            ],
        ],
        'timestamps' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 0,
                    'min' => 0,
                    'max' => 6,
                ],
            ],
            'configs' => [
                'auto_modifiers' => ['nullable'],
                'placeholder' => 'created_at & updated_at',
                'disables' => [
                    'nullableTimestamps', 'timestamps', 'timestampsTz',
                ],
            ],
        ],
        'tinyIncrements' => [
            'configs' => [
                'auto_modifiers' => ['autoIncrement', 'unsigned'],
                'disables' => ['id', 'smallIncrements', 'mediumIncrements', 'increments', 'bigIncrements', 'tinyIncrements'],
            ],
        ],
        'tinyInteger' => [],
        'unsignedBigInteger' => [
            'configs' => [
                'auto_modifiers' => ['unsigned'],
            ],
        ],
        'unsignedDecimal' => [
            'precision' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 4,
                    'min' => 0,
                    'max' => 65,
                ],
            ],
            'scale' => [
                'type' => 'number',
                'attributes' => [
                    'placeholder' => 2,
                    'min' => 0,
                    'max' => 30,
                ],
            ],
            'configs' => [
                'auto_modifiers' => ['unsigned'],
            ],
        ],
        'unsignedInteger' => [
            'configs' => [
                'auto_modifiers' => ['unsigned'],
            ],
        ],
        'unsignedMediumInteger' => [
            'configs' => [
                'auto_modifiers' => ['unsigned'],
            ],
        ],
        'unsignedSmallInteger' => [
            'configs' => [
                'auto_modifiers' => ['unsigned'],
            ],
        ],
        'unsignedTinyInteger' => [
            'configs' => [
                'auto_modifiers' => ['unsigned'],
            ],
        ],
        'uuidMorphs' => [
            'size' => [
                'type' => 'number',
                'force' => 36,
            ],
            'configs' => [
                'placeholder' => 'morphable_id & morphable_type',
            ],
        ],
        'uuid' => [],
        'year' => [],
    ];

    public $selectable_column_types_order = [
        'string',
        'integer',
        'text',
        'date',
        'relationship' => [
            'belongsTo',
            '-',
            'id',
            'uuid',
            'foreignId',
            'belongsTo',
            'morphs',
            'nullableMorphs',
            'uuidMorphs',
            'nullableUuidMorphs',
        ],
        'numeric' => [
            'tinyInteger',
            'smallInteger',
            'mediumInteger',
            'integer',
            'bigInteger',
            '-',
            'decimal',
            'float',
            'double',
            '-',
            'boolean',
            '-',
            'unsignedTinyInteger',
            'unsignedSmallInteger',
            'unsignedMediumInteger',
            'unsignedInteger',
            'unsignedBigInteger',
            'unsignedDecimal',
        ],
        'increments' => [
            'increments',
            'tinyIncrements',
            'smallIncrements',
            'mediumIncrements',
            'bigIncrements',
        ],
        'date and time' => [
            'date',
            'dateTime',
            'dateTimeTz',
            'timestampTz',
            'timestamp',
            'timeTz',
            'time',
            'year',
            '-',
            'timestamps',
            'timestampsTz',
            'softDeletes',
            'softDeletesTz',
            'nullableTimestamps',
        ],
        'string' => [
            'char',
            'string',
            '-',
            'text',
            'mediumText',
            'longText',
            '-',
            'binary',
            '-',
            'enum',
            'set',
            '-',
            'ipAddress',
            'macAddress',
            'rememberToken',
        ],
        'spacial' => [
            'geometry',
            'point',
            'lineString',
            'polygon',
            'multiPoint',
            'multiLineString',
            'multiPolygon',
            'geometryCollection',
        ],
        'json' => [
            'json',
            'jsonb',
        ],
    ];
}
