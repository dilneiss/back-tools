<?php

namespace Backpack\DevTools;

use Illuminate\Support\ServiceProvider;

class DevToolsServiceProvider extends ServiceProvider
{
    use AutomaticServiceProvider;

    protected $vendorName = 'backpack';

    protected $packageName = 'devtools';

    protected $commands = [
        Console\Commands\InstallDevTools::class,
    ];

    protected $livewireComponents = [
        'migration-schema' => Http\Livewire\MigrationSchema::class,
        'relationship-schema' => Http\Livewire\RelationshipSchema::class,
        'publish-modal' => Http\Livewire\Modals\PublishModal::class,
        'create-page-modal' => Http\Livewire\Modals\CreatePageModal::class,
    ];

    protected $binds = [
        GeneratorInterface::class => Generators\BlueprintGenerator::class,
    ];
}
