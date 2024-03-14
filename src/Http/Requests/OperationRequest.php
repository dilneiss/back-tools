<?php

namespace Backpack\DevTools\Http\Requests;

use Backpack\DevTools\CustomFile;
use Backpack\DevTools\Generators\OperationGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class OperationRequest extends FormRequest
{
    use BaseRequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $existingControllers = CustomFile::allFrom(config('backpack.devtools.paths.crud_controllers', []))
            ->filter(function ($file) {
                return $file->isClass() && $file->isCrudController();
            })
            ->pluck('file_path')
            ->toArray();

        $existingOperations = CustomFile::allFrom(config('backpack.devtools.paths.operations', []))
            ->filter(function ($file) {
                return $file->isPhp() && $file->isOperation();
            })
            ->pluck('file_path')
            ->toArray();

        return [
            'path' => 'required|in:'.implode(',', config('backpack.devtools.paths.operations', [])),
            'name' => [
                'required', 'min:2', 'max:255', function ($attribute, $value, $fail) use ($existingOperations) {
                    $fullPath = $this->get('path').'/'.Str::of($value)->studly().'Operation.php';
                    if (in_array($fullPath, $existingOperations)) {
                        $fail('Another operation exists with that name in the given path.');
                    }
                },
            ],
            'controllers' => [
                function ($attribute, $value, $fail) use ($existingControllers) {
                    foreach (($value ?? []) as $controller) {
                        if (! in_array($controller, $existingControllers)) {
                            $fail('The operation chosen doe not seem to exist.');
                        }
                    }
                },
            ],
            'type' => 'required|in:'.implode(',', OperationGenerator::TYPES),
            'button_action' => 'required|in:'.implode(',', OperationGenerator::BUTTON_ACTIONS),
            'confirmation_message' => 'required_if:button_action,'.OperationGenerator::ACTION_CONFIRM_AND_MAKE_AJAX_CALL,
            'button_label' => 'required|min:2|max:255',
        ];
    }
}
