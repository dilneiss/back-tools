<?php

namespace Backpack\DevTools\Generators;

use Backpack\DevTools\CustomFile;
use Backpack\DevTools\Models\Operation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OperationGenerator
{
    public const TYPE_GLOBAL = 'global';

    public const TYPE_LINE = 'line';

    public const TYPE_BULK = 'bulk';

    public const ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM = 'make_get_request_to_backpack_form';

    public const ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW = 'make_get_request_to_custom_view';

    public const ACTION_CONFIRM_AND_MAKE_AJAX_CALL = 'confirm_and_make_ajax_call';

    public const ACTION_MAKE_AJAX_CALL = 'make_ajax_call';

    public const ACTION_OPEN_MODAL = 'open_modal';

    public const BUTTON_ACTIONS = [
        self::ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM,
        self::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW,
        self::ACTION_CONFIRM_AND_MAKE_AJAX_CALL,
        self::ACTION_MAKE_AJAX_CALL,
        self::ACTION_OPEN_MODAL,
    ];

    public const TYPES = [
        self::TYPE_GLOBAL,
        self::TYPE_LINE,
        self::TYPE_BULK,
    ];

    private const PATH_TO_STUBS = '/../Stubs/';

    private const SUFFIX = 'Operation.php';

    private const TEXT_HOLDER_OPERATION_NAME = '___to_be_replaced_with_operation_name___';

    private const TEXT_HOLDER_TRAIT_NAME = '___to_be_replaced_with_trait_operation___';

    private const TEXT_HOLDER_BUTTON_POSITION = '___to_be_replaced_with_button_position___';

    private const TEXT_HOLDER_BUTTON_LABEL = '___to_be_replaced_with_button_label___';

    private const TEXT_HOLDER_CONFIRMATION_MESSAGE = '___to_be_replaced_with_confirmation_message___';

    private const TEXT_HOLDER_NAMESPACE = '___to_be_replaced_with_namespace___';

    private const PLACEHOLDER_BUTTON = '___to_be_replaced_with_button___';

    private const PLACEHOLDER_ROUTE_GET = '___to_be_replaced_with_route_get___';

    private const PLACEHOLDER_ROUTE_POST = '___to_be_replaced_with_route_post___';

    private const PLACEHOLDER_ROUTE_NAME = '___to_be_replaced_with_operation_route_name___';

    private const PLACEHOLDER_FILE_NAME = '___to_be_replaced_with_operation_file_name___';

    private const PLACEHOLDER_METHOD_GET = '___to_be_replaced_with_method_get___';

    private const PLACEHOLDER_METHOD_POST = '___to_be_replaced_with_method_post___';

    private const PLACEHOLDER_ENABLE_BULK_ACTION = '___to_be_replaced_with_enable_bulk_action___';

    private const PLACEHOLDER_OPERATION_HAS_ID = '___to_be_replaced_with_operation_has_id___';

    /**
     * Where to create the new operation.
     */
    private string $path;

    /**
     * Name given by user to the new operation.
     */
    private string $name;

    /**
     * We build this by using the path + name + self::suffix.
     */
    private string $fullPath;

    /**
     * We build this by using the name in cameCase format.
     */
    private string $camelCaseName;

    /**
     * We build this by using the name in snake_case format.
     */
    private string $snakeCaseName;

    /**
     * We build this by using the name in kebab-case format.
     */
    private string $kebabCaseName;

    /**
     * Sample file content used to build the operation.
     */
    private string $template;

    /**
     * A list of controllers to add the new operation after creation.
     */
    private array $controllers;

    /**
     * The type of operation: global, line, bulk.
     */
    private string $type;

    /**
     * The message to display to admins in order to confirm the execution of the operation.
     * Only applies when $buttonLabel is with confirmation message.
     */
    private ?string $confirmationMessage;

    /**
     * Just the button label!
     */
    private string $buttonLabel;

    /**
     * Type of action when the button is clicked:
     *      - make a get request to a custom Backpack view
     *      - make a get request to a custom view
     *      - confirm and make an ajax call
     *      - make an ajax call
     *      - open a modal.
     */
    private string $buttonAction;

    public function __construct(Request $request)
    {
        $this->path = $request->input('path');
        $this->name = Str::of($request->input('name'))->studly();
        $this->camelCaseName = lcfirst(Str::of($this->name)->camel());
        $this->snakeCaseName = Str::of($this->name)->snake();
        $this->kebabCaseName = Str::of($this->name)->kebab();
        $this->type = $request->input('type');
        $this->controllers = $request->input('controllers') ?? [];
        $this->buttonLabel = $request->input('button_label');
        $this->buttonAction = $request->input('button_action');
        $this->confirmationMessage = $request->input('confirmation_message');
    }

    /**
     * @throws Exception
     */
    public function generate(): ?int
    {
        $this->buildTemplate()
            ->buildRequiredViews()
            ->replaceStrings()
            ->save()
            ->addToControllers();

        return $this->getCreatedFileKey();
    }

    private function buildTemplate(): self
    {
        return $this->buildFullPath()->getTemplateContent()->buildOperationTrait();
    }

    public function buildRequiredViews(): self
    {
        return $this->buildAndSaveButtonView()->buildAndSaveCustomView();
    }

    private function buildAndSaveButtonView(): self
    {
        $directory = resource_path('views/vendor/backpack/crud/buttons/');
        $this->createDirectoryIfDoesntExist($directory);
        $path = $directory.$this->snakeCaseName.'.blade.php';

        // Check if View already exists
        if (File::exists($path)) {
            return $this;
        }

        $buttonViewContent = File::get(__DIR__.self::PATH_TO_STUBS.'views/button_'.$this->type.'/'.$this->buttonAction.'.stub');

        $this->replaceFileContent($buttonViewContent, self::TEXT_HOLDER_BUTTON_LABEL, $this->buttonLabel);
        $this->replaceFileContent($buttonViewContent, self::TEXT_HOLDER_OPERATION_NAME, $this->camelCaseName);
        $this->replaceFileContent($buttonViewContent, self::PLACEHOLDER_ROUTE_NAME, $this->kebabCaseName);

        if (! empty($this->confirmationMessage)) {
            $this->replaceFileContent($buttonViewContent, self::TEXT_HOLDER_CONFIRMATION_MESSAGE, $this->confirmationMessage);
        }

        File::put($path, $buttonViewContent);

        return $this;
    }

    private function buildAndSaveCustomView(): self
    {
        if (! $this->shouldBuildView()) {
            return $this;
        }

        $directory = resource_path('views/vendor/backpack/crud/operations/');
        $this->createDirectoryIfDoesntExist($directory);
        $path = $directory.$this->snakeCaseName.'.blade.php';

        // Check if View already exists
        if (File::exists($path)) {
            return $this;
        }

        $methodType = $this->type === 'global' && $this->buttonAction !== self::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW ? '_global' : '';
        $customViewContent = File::get(__DIR__.self::PATH_TO_STUBS.'views/'.$this->buttonAction.$methodType.'.stub');

        File::put($path, $customViewContent);

        return $this;
    }

    private function replaceStrings(): self
    {
        return $this->replaceNamespace()
            ->replaceTraitName()
            ->replaceButtonStrings()
            ->replaceFileNameStrings()
            ->replacePathStrings()
            ->replaceWithIdOnRoutes();
    }

    private function buildOperationTrait(): self
    {
        $requiresGetRoute = $this->shouldBuildView();
        $hasPostMethod = $this->hasPostMethod();
        $withId = $this->type === self::TYPE_LINE ? '_with_id' : '';

        // Route GET
        $getRouteContent = $requiresGetRoute
            ? File::get(__DIR__.self::PATH_TO_STUBS.'inc/routes/get'.$withId.'.stub').'        '
            : '';
        $this->replaceFileContent($this->template, self::PLACEHOLDER_ROUTE_GET, $getRouteContent);

        // Route POST
        $postRouteContent = $hasPostMethod ? File::get(__DIR__.self::PATH_TO_STUBS.'inc/routes/post'.$withId.'.stub') : '';
        $this->replaceFileContent($this->template, self::PLACEHOLDER_ROUTE_POST, $postRouteContent);

        // Method GET
        $getPath = $this->buttonAction !== self::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW
            ? $withId
            : '_for_custom_view';
        $getMethodContent = $requiresGetRoute
            ? File::get(__DIR__.self::PATH_TO_STUBS.'inc/methods/get'.$getPath.'.stub').PHP_EOL.'    '
            : '';
        $this->replaceFileContent($this->template, self::PLACEHOLDER_METHOD_GET, $getMethodContent);

        // Route name
        $this->replaceFileContent($this->template, self::PLACEHOLDER_ROUTE_NAME, $this->kebabCaseName);

        // Method POST
        $postMethodContent = $hasPostMethod ?
                                (
                                    $this->buttonAction === self::ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM ?
                                        File::get(__DIR__.self::PATH_TO_STUBS.'inc/methods/post_form'.$withId.'.stub') :
                                        File::get(__DIR__.self::PATH_TO_STUBS.'inc/methods/post'.$withId.'.stub')
                                ) :
                                '';

        $this->replaceFileContent($this->template, self::PLACEHOLDER_METHOD_POST, $postMethodContent);

        // Button
        $buttonContent = File::get(__DIR__.self::PATH_TO_STUBS.'inc/button.stub');
        $this->replaceFileContent($this->template, self::PLACEHOLDER_BUTTON, $buttonContent);

        $enableBulk = $this->type === self::TYPE_BULK ? PHP_EOL.'            $this->crud->enableBulkActions();' : '';
        $this->replaceFileContent($this->template, self::PLACEHOLDER_ENABLE_BULK_ACTION, $enableBulk);

        return $this;
    }

    private function buildFullPath(): self
    {
        $this->fullPath = $this->path.'/'.$this->name.self::SUFFIX;

        return $this;
    }

    /**
     * @throws Exception
     */
    private function save(): self
    {
        $this->createDirectoryIfDoesntExist($this->path);

        if (! File::put($this->fullPath, $this->template)) {
            throw new Exception('Operation was not generated.');
        }

        return $this;
    }

    private function getCreatedFileKey(): ?int
    {
        Operation::clearBootedModels();

        return Operation::orderBy('file_created_at', 'desc')->first()->getKey();
    }

    private function getTemplateContent(): self
    {
        $templateName = $this->buttonAction === self::ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM ? 'trait_with_form.stub' : 'trait.stub';

        $this->template = File::get(__DIR__.self::PATH_TO_STUBS.$templateName);

        return $this;
    }

    private function getNamespace(): string
    {
        $namespace = 'App'.str_replace(app_path(), '', $this->path);

        return str_replace('/', '\\', $namespace);
    }

    private function replaceNamespace(): self
    {
        $this->replaceFileContent($this->template, self::TEXT_HOLDER_NAMESPACE, $this->getNamespace());

        return $this;
    }

    private function replaceTraitName(): self
    {
        $this->replaceFileContent($this->template, self::TEXT_HOLDER_TRAIT_NAME, $this->name);

        return $this;
    }

    private function replaceWithIdOnRoutes(): self
    {
        $this->replaceFileContent($this->template, self::PLACEHOLDER_OPERATION_HAS_ID, $this->type === 'line' ? 'true' : 'false');

        return $this;
    }

    private function replaceButtonStrings(): self
    {
        switch ($this->type) {
            case self::TYPE_GLOBAL:
                $buttonPosition = 'top';
                break;
            case self::TYPE_LINE:
                $buttonPosition = 'line';
                break;
            case self::TYPE_BULK:
                $buttonPosition = 'bottom';
                break;
            default:
                // Note: It should not be reached since it was already validated!
                $buttonPosition = '';
        }

        $this->replaceFileContent($this->template, self::TEXT_HOLDER_BUTTON_POSITION, $buttonPosition);
        $this->replaceFileContent($this->template, self::TEXT_HOLDER_BUTTON_LABEL, $this->buttonLabel);

        return $this;
    }

    private function replaceFileNameStrings(): self
    {
        $this->replaceFileContent($this->template, self::PLACEHOLDER_FILE_NAME, $this->snakeCaseName);

        return $this;
    }

    private function replacePathStrings(): self
    {
        $this->replaceFileContent($this->template, self::TEXT_HOLDER_OPERATION_NAME, $this->camelCaseName);

        return $this;
    }

    /**
     * We divide the Controller file content in three parts to be able to manipulate it:
     *   1- Content before Class definition — E.g. "Class MyController extends CrudController"
     *   2- Content before methods -> here is where we look for "use" statements to place at the end the new one
     *   3- Content methods.
     *
     * @return void
     */
    private function addToControllers(): void
    {
        foreach ($this->controllers ?? [] as $controllerPath) {
            // Load Controller file
            $controller = CustomFile::allFrom(config('backpack.devtools.paths.crud_controllers', []))
                ->filter(function ($file) use ($controllerPath) {
                    return $file->isClass() && $file->isCrudController() && $file->file_path === $controllerPath;
                })
                ->first();

            $classDefinition = $controller->class_name.' extends CrudController';

            // If Controller does not extend CrudController, we ignore it
            if (! Str::contains($controller->file_contents, $classDefinition)) {
                continue;
            }

            // 1- Get content before Class definition
            $beforeClassDefinitionSection = Str::before($controller->file_contents, $classDefinition).$classDefinition.PHP_EOL.'{';

            // 2- Get content after Class definition and make sure we are working after "{"
            $afterClassDefinitionSection = Str::after(Str::after($controller->file_contents, $classDefinition), '{');

            // 3- Get existing use statements and methods content
            $useStatementsSection = Str::before($afterClassDefinitionSection, 'function').'function';
            $methodsSection = Str::after($afterClassDefinitionSection, 'function');

            // Place use statement at the end of existing ones
            if (Str::contains($useStatementsSection, 'use ')) {
                $beforeLastUseStatement = Str::beforeLast($useStatementsSection, ';').';'.PHP_EOL;
                $beforeLastUseStatement .= $this->buildUseOperationForController();
                $afterLastUseStatement = Str::afterLast($useStatementsSection, ';');
                $useStatementsSection = $beforeLastUseStatement.$afterLastUseStatement;
            } else {
                // Add use statement right before the first method
                $useStatementsSection = PHP_EOL.$this->buildUseOperationForController().PHP_EOL.$useStatementsSection;
            }

            // Build file contents
            $controller->file_contents = $beforeClassDefinitionSection;
            $controller->file_contents .= $useStatementsSection;
            $controller->file_contents .= $methodsSection;

            // Rewrite Controller file
            File::put($controller->file_path, $controller->file_contents);
        }
    }

    private function buildUseOperationForController(): string
    {
        $str = '    use \\App'.str_replace(app_path(), '', $this->path);
        $str .= '\\'.Str::of($this->name)->studly().'Operation;';

        return str_replace('/', '\\', $str);
    }

    private function shouldBuildView(): bool
    {
        return $this->buttonAction === self::ACTION_MAKE_GET_REQUEST_TO_BACKPACK_FORM
            || $this->buttonAction === self::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW
            || $this->buttonAction === self::ACTION_OPEN_MODAL;
    }

    private function hasPostMethod(): bool
    {
        return ! in_array($this->buttonAction, [self::ACTION_MAKE_GET_REQUEST_TO_CUSTOM_VIEW, self::ACTION_OPEN_MODAL]);
    }

    private function createDirectoryIfDoesntExist(string $path)
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true);
        }
    }

    private function replaceFileContent(string &$content, string $search, string $replace): void
    {
        $content = Str::replace($search, $replace, $content);
    }
}
