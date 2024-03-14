<?php

namespace Backpack\DevTools\Http\Controllers\Operations;

use Alert;
use Artisan;
use Illuminate\Support\Facades\Route;
use Str;

trait RunMigrationOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment    Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName  Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     */
    protected function setupRunMigrationRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/{id}/run-migration', [
            'as' => $routeName.'.runMigration',
            'uses' => $controller.'@runMigration',
            'operation' => 'runMigration',
        ]);

        Route::get($segment.'/{id}/rollback-migration', [
            'as' => $routeName.'.rollbackMigration',
            'uses' => $controller.'@rollbackMigration',
            'operation' => 'rollbackMigration',
        ]);

        Route::get($segment.'/migrate-rollback', [
            'as' => $routeName.'.migrateRollback',
            'uses' => $controller.'@migrateRollback',
            'operation' => 'migrateRollback',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupRunMigrationDefaults()
    {
        $this->crud->allowAccess('runMigration');

        $this->crud->operation('runMigration', function () {
            $this->crud->loadDefaultOperationSettingsFromConfig();
        });

        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addButton('line', 'runMigration', 'view', 'backpack.devtools::buttons.migration_run', 'end');
        });

        $this->crud->operation(['list'], function () {
            $this->crud->addButton('top', 'rollback', 'view', 'backpack.devtools::buttons.migrate_rollback', 'end');
        });
    }

    /**
     * Show the view for performing the operation.
     *
     * @return Response
     */
    public function runMigration($id)
    {
        $this->crud->hasAccessOrFail('runMigration');

        $id = $this->crud->getCurrentEntryId() ?? $id;
        $entry = $this->crud->getEntry($id);
        $output = $entry->run();

        if ($output === true) {
            Alert::success('The migration has been run successfully.')->flash();
        } else {
            Alert::warning(nl2br($output))->flash();
        }

        return redirect()->back();
    }

    /**
     * Show the view for performing the operation.
     *
     * @return Response
     */
    public function rollbackMigration($id)
    {
        $this->crud->hasAccessOrFail('runMigration');

        $id = $this->crud->getCurrentEntryId() ?? $id;
        $entry = $this->crud->getEntry($id);
        $output = $entry->rollback();

        if ($output === true) {
            Alert::success('The migration has been rolled back successfully.')->flash();
        } else {
            Alert::warning(nl2br($output))->flash();
        }

        return redirect()->back();
    }

    /**
     * Runs artisan command migrate rollback.
     *
     * @return Response
     */
    public function migrateRollback()
    {
        $this->crud->hasAccessOrFail('runMigration');

        Artisan::call('migrate:rollback', ['--force' => true]);

        $output = Artisan::output();
        $migrations = collect(explode(PHP_EOL, $output))
            ->filter(function ($line) {
                return Str::of($line)->startsWith('Rolled back');
            })
            ->map(function ($line) {
                return Str::of($line)->remove('Rolled back: ');
            })
            ->toArray();

        if (count($migrations) > 0) {
            Alert::success(nl2br("Successfully rolled back:\n".implode("\n", $migrations)))->flash();
        } else {
            Alert::warning(nl2br($output))->flash();
        }

        return redirect()->back();
    }
}
