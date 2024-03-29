<?php

namespace Backpack\DevTools\Http\Controllers\Operations;

use Alert;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait AddCrudTraitToModel
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment    Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName  Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     */
    protected function setupAddCrudTraitToModelRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/{id}/add-crud-trait', [
            'as'        => $routeName.'.addCrudTrait',
            'uses'      => $controller.'@addCrudTrait',
            'operation' => 'addCrudTraitToModel',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupAddCrudTraitToModelDefaults()
    {
        $this->crud->allowAccess('addCrudTrait');

        $this->crud->operation('addCrudTraitToModel', function () {
            $this->crud->loadDefaultOperationSettingsFromConfig();
        });

        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addButton('line', 'addCrudTraitButton', 'view', 'backpack.devtools::buttons.add_crud_trait');
        });
    }

    /**
     * Add the CrudTrait to models that does not have them.
     *
     * @return Response
     */
    public function addCrudTrait($id)
    {
        $this->crud->hasAccessOrFail('addCrudTrait');

        $id = $this->crud->getCurrentEntryId() ?? $id;
        $entry = $this->crud->getEntry($id);
        $content = Str::of(File::get($entry->file));

        // check if it already uses CrudTrait
        if (! $entry->hasCrudTrait) {
            // import the CrudTrait namespace if it doesn't already exist
            if (! $content->contains('use Backpack\CRUD\app\Models\Traits\CrudTrait;')) {
                $modifiedContent = Str::of($content->before('namespace'))
                                        ->append('namespace'.$content->after('namespace')->before(';'))
                                        ->append(';'.PHP_EOL.PHP_EOL.'use Backpack\CRUD\app\Models\Traits\CrudTrait;');

                $content = $content->after('namespace')->after(';');

                while (str_starts_with($content, PHP_EOL) || str_starts_with($content, "\n")) {
                    $content = substr($content, 1);
                }

                $modifiedContent = $modifiedContent->append(PHP_EOL.$content);

                // use the CrudTrait on the class
                $modifiedContent = $modifiedContent->replaceFirst('{', '{'.PHP_EOL.'    use CrudTrait;');

                File::put($entry->file, $modifiedContent);
                Alert::success('CrudTrait added to model.');
            }
        } else {
            Alert::success('CrudTrait was already on the model.');
        }

        return redirect()->back();
    }
}
