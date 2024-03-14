<?php

namespace Backpack\Devtools\Http\Livewire\Modals;

use Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Livewire\Component;

class CreatePageModal extends Component
{
    public $name;

    public $route;

    public $preview;

    public $routeEdited = false;

    public $errors = [];

    public $routeCollection;

    public $prefix;

    public function mount()
    {
        $this->prefix = rtrim(config('backpack.base.route_prefix'), '\\/');
        $this->initModal();
    }

    public function updatedName(): void
    {
        if (! $this->routeEdited) {
            $this->route = Str::of($this->name)->after("$this->prefix/");
        }

        $this->updateView();
    }

    public function updatedRoute(): void
    {
        $this->routeEdited = true;

        $this->updateView();
    }

    private function getNameValue(): Stringable
    {
        return Str::of($this->name)
            ->replace('\\', '/')
            ->trim('/')
            ->afterLast('/');
    }

    private function getPathValue(): Stringable
    {
        return Str::of($this->name)
            ->replace('/', '\\')
            ->trim('\\')
            ->match('/.+\\\/')
            ->finish('\\')
            ->ltrim('\\');
    }

    private function getRouteValue(): Stringable
    {
        return Str::of($this->route)
            ->snake('-')
            ->replace('\\', '/')
            ->trim('/');
    }

    public function updateView(): void
    {
        if (! $this->name || ! $this->route || $this->name === "$this->prefix/" || preg_match('/\w$/u', $this->name) === 0) {
            $this->preview = null;

            return;
        }

        $this->errors = [];
        $view = 'resources\\views\\'.$this->getPathValue().$this->getNameValue()->snake()->replace('-', '_').'.blade.php';
        $route = $this->prefix.'/'.$this->getRouteValue();
        $controller = 'app\\Http\\Controllers\\Admin\\'.$this->getNameValue()->studly().'Controller.php';

        // Validate route
        if (array_search($route, $this->routeCollection)) {
            $this->errors['route'] = 'Route already exists!';
        }

        // Validate view
        if (file_exists(base_path($view))) {
            $this->errors['view'] = 'View already exists!';
        }

        $this->preview = [
            'controller' => $controller ?? '',
            'view' => $view ?? '',
            'url' => url($route) ?? '',
        ];
    }

    public function initModal(): void
    {
        $this->name = "$this->prefix/";
        $this->route = '';
        $this->routeEdited = false;
        $this->errors = [];
        $this->preview = null;

        $this->routeCollection = collect(Route::getRoutes())
            ->map(function ($route) {
                return $route->uri;
            })
            ->filter(function ($uri) {
                return str_starts_with($uri, $this->prefix) && ! str_contains($uri, '{');
            })
            ->toArray();
    }

    public function resetRoute(): void
    {
        $this->route = $this->getNameValue()->after($this->prefix);
        $this->routeEdited = false;
        $this->updateView();
    }

    public function formSubmit(): bool
    {
        Artisan::resolved(function () {
            chdir(base_path());
        });

        $name = $this->getNameValue()->snake();
        $path = $this->getPathValue();
        $route = $this->getRouteValue();

        // create the file
        Artisan::call('backpack:page', ['name' => $name, '--view-path' => $path, '--route' => $route]);
        $output = Str::of(Artisan::output());

        // handle errors
        if ($output->contains('ALREADY EXISTED')) {
            $this->dispatchEvent('The page already exists.', false);

            return false;
        }

        if ($output->contains('ERROR')) {
            $this->dispatchEvent('Something went wrong, please check the logs for more info.', false);

            return false;
        }

        // success
        $this->dispatchEvent('Successfully created the page.');

        return true;
    }

    private function dispatchEvent(string $message, bool $success = true): void
    {
        $this->dispatch('create-page-modal', [
            'title' => 'Create Page',
            'message' => $message,
            'success' => $success,
        ]);
    }

    public function render()
    {
        return view('backpack.devtools::livewire.modals.create-page-modal');
    }
}
