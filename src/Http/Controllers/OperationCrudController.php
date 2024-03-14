<?php

namespace Backpack\DevTools\Http\Controllers;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Backpack\DevTools\CustomFile;
use Backpack\DevTools\GeneratorInterface;
use Backpack\DevTools\Generators\OperationGenerator;
use Backpack\DevTools\Http\Requests\OperationRequest;
use Backpack\DevTools\Models\Model;
use Backpack\DevTools\Models\Operation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;

/**
 * Class ModelCrudController.
 *
 * @property CrudPanel $crud
 */
class OperationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\DevTools\Http\Controllers\Operations\AddCrudTraitToModel;
    use \Backpack\DevTools\Http\Controllers\Operations\StrippedShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }

    public function setup()
    {
        CRUD::setModel(Operation::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/devtools/operation');
        CRUD::setEntityNameStrings('operation', 'operations');

        Config::set('backpack.base.breadcrumbs', false);

        Widget::add()
            ->to('before_breadcrumbs')
            ->type('view')
            ->view('backpack.devtools::widgets.menu');
    }

    protected function setupListOperation()
    {
        Model::clearBootedModels();

        CRUD::orderBy('file_created_at', 'desc');
        CRUD::column('name')
            ->label('Name')
            ->type('view')
            ->view('backpack.devtools::columns.file-link')
            ->filePath('file_path')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->where('file_path', 'LIKE', '%'.$searchTerm.'%');
            })
            ->limit(100);
        CRUD::column('class_namespace');
        CRUD::column('crud_controller_files')
            ->label('Used in')
            ->type('view')
            ->view('backpack.devtools::columns.files-link')
            ->searchLogic(function ($query, $column, $searchTerm) {
                $query->where('file_path', 'LIKE', "%$searchTerm%");
            });
        CRUD::removeButton('show');
        CRUD::addButton('line', 'details', 'view', 'backpack.devtools::buttons.details', 'beginning');
    }

    protected function setupShowOperation()
    {
        CRUD::setShowContentClass('col-md-12');

        CRUD::set('show.setFromDb', false);

        CRUD::column('file_path_from_base')
            ->type('view')
            ->view('backpack.devtools::columns.file-link')
            ->filePath('file_path');

        CRUD::column('crud_controller_files')
            ->label('Used in')
            ->type('view')
            ->view('backpack.devtools::columns.files-link');

        CRUD::column('related_view_files')
            ->label('Related view files')
            ->type('view')
            ->view('backpack.devtools::columns.related-view-files');

        CRUD::column('file_contents')
            ->type('view')
            ->view('backpack.devtools::columns.code');

        CRUD::column('file_created_at')
            ->type('datetime')
            ->label('Date');

        CRUD::removeButton('details');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(OperationRequest::class);
        CRUD::setCreateContentClass('col-12');

        $paths = [];
        foreach (config('backpack.devtools.paths.operations', []) as $path) {
            $paths[$path] = str_replace('\\', '/', str_replace(app_path(), '', $path)).'/';
        }

        CRUD::field('path')
            ->type('select2_from_array')
            ->label('Directory')
            ->hint('The directory where you want to create the new operation.')
            ->options($paths)
            ->default(array_key_first($paths))
            ->size(4);

        CRUD::field('name')
            ->type('text')
            ->label('Name')
            ->suffix('Operation.php')
            ->attributes([
                'placeholder' => 'ApproveInvoice',
            ])
            ->hint('Use the operation name in singular and StudlyCase.')
            ->size(4);

        CRUD::field('type')
            ->type('select2_from_array')
            ->label('Type')
            ->options([
                OperationGenerator::TYPE_GLOBAL => 'Global - e.g. "Mark All as Read"',
                OperationGenerator::TYPE_LINE => 'Line - e.g. "Publish"',
                OperationGenerator::TYPE_BULK => 'Bulk - e.g. "Remove"',
            ])
            ->size(4);

        CRUD::field('button_label')
            ->type('text')
            ->attributes([
                'placeholder' => 'Remove inactive users',
            ])
            ->label('Button label')
            ->size(6);

        CRUD::field('button_action')
            ->type('select2_from_array')
            ->label('Button action')
            ->options([
                OperationGenerator::ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM => 'Redirect to a GET route showing a custom Backpack form',
                OperationGenerator::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW => 'Redirect to a GET route showing a custom view',
                OperationGenerator::ACTION_CONFIRM_AND_MAKE_AJAX_CALL => 'Make AJAX call to a POST route (after showing confirmation message)',
                OperationGenerator::ACTION_MAKE_AJAX_CALL => 'Make an AJAX call to a POST route',
//                OperationGenerator::ACTION_OPEN_MODAL => 'Modal', // TODO
            ])
            ->size(6)
            ->hint('What happens when an admin clicks on the operation button?');

        CRUD::field('confirmation_message')
            ->type('text')
            ->attributes([
                'placeholder' => 'Are you sure you want to do this?',
            ])
            ->label('Confirmation message');

        CRUD::field('controllers')
            ->type('select2_from_array')
            ->allows_multiple(true)
            ->label('Where do you want to use this operation?')
            ->hint('Select the Controller(s) that you want Backpack to add this operation.')
            ->options(
                CustomFile::allFrom(config('backpack.devtools.paths.crud_controllers', []))
                    ->filter(function ($file) {
                        return $file->isClass() && $file->isCrudController();
                    })
                    ->pluck('file_path_relative', 'file_path')
                    ->toArray()
            );

        CRUD::removeSaveAction('save_and_preview');

        CRUD::addSaveAction([
            'name' => 'save_and_preview',
            'redirect' => function ($crud, $request, $itemId = null) {
                $itemId = $itemId ?: $request->input('id');
                $redirectUrl = $crud->route.'/'.$itemId.'/show?new=1';
                if ($request->has('locale')) {
                    $redirectUrl .= '&locale='.$request->input('locale');
                }

                return $redirectUrl;
            },
            'button_text' => 'Save and preview',
            'visible' => true,
            'order' => 1,
        ]);

        CRUD::field('js')->type('view')->view('backpack.devtools::operations.js');
    }

    /**
     * @param  GeneratorInterface  $generator
     * @return RedirectResponse
     */
    public function store(GeneratorInterface $generator): RedirectResponse
    {
        CRUD::hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = CRUD::validateRequest();

        // build migration - create and run blueprint yaml
        $key = $generator->generate($request);

        // save the redirect choice for next time
        CRUD::setSaveAction();

        return CRUD::performSaveAction($key);
    }
}
