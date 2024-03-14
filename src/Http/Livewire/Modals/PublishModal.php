<?php

namespace Backpack\Devtools\Http\Livewire\Modals;

use Artisan;
use Backpack\CRUD\ViewNamespaces;
use Backpack\Devtools\CustomFile;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Str;

class PublishModal extends Component
{
    public const CREATE = 'create';

    public const PUBLISH = 'publish';

    public $mode = self::CREATE;

    public $inputName = null;

    public $selectedFile = null;

    public $selectedFileType = 'button';

    public $selectedFileTypePath;

    public $allOptions;

    public $visibleOptions;

    public function mount()
    {
        $viewHints = view()->getFinder()->getHints();

        $this->allOptions = collect(['button', 'column', 'field', 'filter', 'widget'])
            ->mapWithKeys(function ($option) use ($viewHints) {
                $optionPlural = Str::plural($option);
                $namespaces = ViewNamespaces::getFor($optionPlural);

                $files = collect([...$namespaces, 'backpack'])
                    ->mapWithKeys(function ($namespace) use ($optionPlural, $viewHints) {
                        $namespace = Str::of($namespace)->before('::');
                        $title = (string) $namespace->replace('backpack.', '')->replace('-', ' ');

                        $files = collect($viewHints[(string) $namespace] ?? [])
                            ->filter(function ($hint) {
                                // ignore files in user scope
                                return ! Str::of($hint)->startsWith(resource_path());
                            })
                            ->flatMap(function ($hint) use ($optionPlural) {
                                return $this->getFiles("$hint/$optionPlural");
                            })
                            ->toArray();

                        return [$title => $files];
                    })
                    ->filter();

                return [$option => $files];
            });

        $this->visibleOptions = $this->allOptions[$this->selectedFileType];
    }

    public function initModal(string $type): void
    {
        $this->mode = self::CREATE;
        $this->inputName = null;
        $this->selectedFileType = $type;
        $this->selectedFile = array_keys($this->allOptions[$this->selectedFileType]['crud'] ?? $this->allOptions[$this->selectedFileType]['pro'] ?? [])[0] ?? null;
        $this->visibleOptions = $this->allOptions[$this->selectedFileType];
    }

    private function getFiles(string $path): array
    {
        return CustomFile::allFrom($path)
            ->values()
            ->pluck('file_name_with_extension', 'file_path')
            ->map(function ($item) {
                return str_replace('.blade.php', '', $item);
            })
            ->toArray();
    }

    public function formSubmit(): bool
    {
        switch ($this->mode) {
            case self::CREATE:
                return $this->createFile();
            case self::PUBLISH:
                return $this->publishFile();
        }
    }

    private function createFile(): bool
    {
        Artisan::resolved(function () {
            chdir(base_path());
        });

        // validate input
        if (! $this->inputName) {
            $this->dispatchEvent(self::CREATE, 'The name cannot be empty.', false);

            return false;
        }

        // create the file
        Artisan::call("backpack:{$this->selectedFileType} {$this->inputName}");
        $output = Str::of(Artisan::output());

        // handle errors
        if ($output->contains('ALREADY EXISTED')) {
            $this->dispatchEvent(self::CREATE, "The {$this->selectedFileType} already exists.", false);

            return false;
        }

        if ($output->contains('ERROR')) {
            $this->dispatchEvent(self::CREATE, 'Something went wrong, please check the logs for more info.', false);

            return false;
        }

        // success
        $this->dispatchEvent(self::CREATE, "Successfully created {$this->selectedFileType}.");

        return true;
    }

    private function publishFile(): bool
    {
        Artisan::resolved(function () {
            chdir(base_path());
        });

        // clean selected file
        $this->selectedFile = Str::of($this->selectedFile)->replace('\\', '/')->after(base_path());
        $selectedFileName = $this->selectedFile->afterLast('/')->remove('.blade.php');

        // create the file
        Artisan::call("backpack:{$this->selectedFileType} {$selectedFileName} --from='{$this->selectedFile}'");
        $output = Str::of(Artisan::output());

        // handle errors
        if ($output->contains('ALREADY EXISTED')) {
            $this->dispatchEvent(self::PUBLISH, "The {$this->selectedFileType} already exists.", false);

            return false;
        }

        if ($output->contains('ERROR')) {
            Log::error($output);
            $this->dispatchEvent(self::PUBLISH, 'Something went wrong, please check the logs for more info.', false);

            return false;
        }

        // success
        $this->dispatchEvent(self::PUBLISH, "Successfully published {$this->selectedFileType}.");

        return true;
    }

    public function changeMode(string $mode): void
    {
        $this->mode = $mode;
        $this->dispatch('publish-modal-mode', $this->mode);
    }

    private function dispatchEvent(string $event, string $message, bool $success = true): void
    {
        $this->dispatch('publish-modal', [
            'title' => Str::of("{$event} {$this->selectedFileType}")->title(),
            'message' => $message,
            'success' => $success,
        ]);
    }

    public function render()
    {
        return view('backpack.devtools::livewire.modals.publish-modal');
    }
}
