<?php

namespace Backpack\DevTools\Http\Livewire\Traits;

trait HasDatabaseModifiers
{
    public $column_modifiers = [
        'autoIncrement' => [
            'valid_for' => [
                'id', 'bigIncrements', 'smallIncrements', 'tinyIncrements', 'mediumIncrements', 'increments', 'integer', 'bigInteger', 'unsignedBigInteger', 'tinyInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger', 'smallInteger', 'mediumInteger', 'increments',
            ],
            'type' => 'checkbox',
            'invalidates_on_select' => [
                'nullable', 'default',
            ],
            'operations' => [
                'create', 'update',
            ],
        ],
        'unique' => [
            'invalid_for' => [
                'timestamps', 'timestampsTz',
            ],
            'invalidates_on_select' => [
                'nullable', 'default',
            ],
            'type' => 'checkbox',
            'operations' => [
                'create',
            ],
        ],
        'index' => [
            'invalid_for' => [
                'timestamps', 'timestampsTz',
            ],
            'type' => 'checkbox',
            'operations' => [
                'create',
            ],
        ],
        'unsigned' => [
            'valid_for' => [
                'id', 'bigIncrements', 'integer', 'bigInteger', 'tinyInteger', 'smallInteger', 'mediumInteger', 'unsignedBigInteger', 'unsignedMediumInteger', 'unsignedTinyInteger', 'increments',
            ],
            'type' => 'checkbox',
            'operations' => [
                'create', 'update',
            ],
        ],
        'useCurrent' => [
            'valid_for' => [
                'dateTime', 'dateTimeTz', 'timestamp', 'timestampTz',
            ],
            'type' => 'checkbox',
            'operations' => [
                'create', 'update',
            ],
        ],
        'useCurrentOnUpdate' => [
            'valid_for' => [
                'dateTime', 'dateTimeTz', 'timestamp', 'timestampTz',
            ],
            'type' => 'checkbox',
            'operations' => [
                'create', 'update',
            ],
        ],
        'first' => [
            'invalid_for' => [
                'timestamps', 'timestampsTz',
            ],
            'type' => 'checkbox',
            'operations' => [
                'update',
            ],
        ],
        'default' => [
            'invalid_for' => [
                'id', 'bigIncrements', 'smallIncrements', 'tinyIncrements', 'mediumIncrements', 'increments',
            ],
            'type' => 'text',
            'operations' => [
                'create', 'update',
            ],
        ],

        'after' => [
            'type' => 'text',
            'operations' => [
                'update',
            ],
        ],

        'charset' => [
            'operations' => [
                'create', 'update',
            ],
        ],

        'comment' => [
            'type' => 'text',
            'operations' => [
                'create', 'update',
            ],
        ],

        'collation' => [
            'operations' => [
                'create', 'update',
            ],
        ],
        'from' => [
            'valid_for' => [
                'bigIncrements', 'smallIncrements', 'tinyIncrements', 'mediumIncrements', 'increments',
            ],
            'type' => 'number',
            'operations' => [
                'create', 'update',
            ],
        ],
        'nullable' => [
            'invalid_for' => [
                'id', 'bigIncrements', 'smallIncrements', 'tinyIncrements', 'mediumIncrements', 'increments',
            ],
            'invalidates_on_select' => [
                'autoIncrement',
            ],
            'type' => 'checkbox',
            'operations' => [
                'create', 'update',
            ],
        ],
    ];
}
