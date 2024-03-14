<?php

namespace Backpack\DevTools\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallDevTools extends Command
{
    use \Backpack\CRUD\app\Console\Commands\Traits\PrettyCommandOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backpack:devtools:install
                            {--debug} : Show process output or not. Useful for debugging.';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install DevTools requirements on dev.';

    /**
     * @var string|null
     */
    protected $envFileContent;

    private const EDITORS = ['vscode', 'vscode-insiders', 'subl', 'sublime', 'textmate', 'emacs', 'macvim', 'phpstorm', 'idea', 'atom', 'nova', 'netbeans', 'xdebug'];

    /**
     * Execute the console command.
     *
     * @return mixed Command-line output
     */
    public function handle()
    {
        $this->infoBlock('DevTools installation started.');

        // Sidebar
        $this->addSidebarEntry();

        // Config
        $this->publishConfig();

        // Editor
        $this->setupEditor();

        // Finish
        $this->infoBlock('DevTools installation finished.');

        // Notice for prod
        $this->newLine();
        $this->error('                                                                         ');
        $this->error('                             IMPORTANT!!!                                ');
        $this->error('                 DO NOT install DevTools in production.                  ');
        $this->error('            You don\'t want your admins to have access to it.             ');
        $this->error('                                                                         ');
        $this->newLine();
        $this->line('  Make sure your build & deploy scripts use `composer install --no-dev`  ', 'fg=red');
        $this->line('           or uninstall DevTools after you\'re done, by running           ', 'fg=red');
        $this->line('                `composer remove --dev backpack/devtools`                ', 'fg=red');
        $this->newLine();
    }

    public function addSidebarEntry()
    {
        $path = resource_path('views/vendor/backpack/ui/inc/menu_items.blade.php');
        $this->progressBlock("Adding Devtools to sidebar <fg=gray>$path</>");

        if (! File::exists($path)) {
            $this->errorProgressBlock();
            $this->note('The menu_items file does not exist.', 'red');

            return;
        }

        $content = Str::of(File::get($path));

        if ($content->contains('devtools')) {
            $this->closeProgressBlock('Not needed', 'yellow');
            $this->note('Sidebar item already existed.');

            return;
        }

        $sidebarEntry = implode(PHP_EOL, [
            "@includeWhen(class_exists(\Backpack\DevTools\DevToolsServiceProvider::class), 'backpack.devtools::buttons.sidebar_item')",
            '',
        ]);

        // Add after dashboard
        if ($content->contains('dashboard')) {
            $content = preg_replace('/(dashboard.+)/', '$1'.PHP_EOL.$sidebarEntry, (string) $content);
        }
        // Add on top
        else {
            $content = $sidebarEntry.PHP_EOL.$content;
        }

        // Save file
        if (File::put($path, $content)) {
            $this->closeProgressBlock();
        } else {
            $this->errorProgressBlock();
            $this->note('Could not write to sidebar_content file.', 'red');
        }
    }

    public function publishConfig()
    {
        $this->newLine();
        $this->progressBlock('Publishing DevTools config file');

        if (File::exists(config_path('backpack/devtools.php'))) {
            $this->closeProgressBlock('Not needed', 'yellow');
            $this->note('Config file already existed.');

            return;
        }

        $this->newLine();
        $this->note('Publishing config file may be useful if your Models/Controllers are in non-standard places.');

        $publish = $this->confirm(' Publish DevTools config file?', false);
        $this->deleteLines(6);

        $this->progressBlock('Publishing DevTools config file');

        if ($publish) {
            $this->executeArtisanProcess('vendor:publish', [
                '--provider' => \Backpack\DevTools\DevToolsServiceProvider::class,
                '--tag' => 'config',
            ]);

            $this->closeProgressBlock();
            $this->note('Config file published.');
        } else {
            $this->closeProgressBlock('skipped', 'blue');
            $this->note('Config file skipped.');
        }
    }

    public function setupEditor()
    {
        $this->newLine();
        $this->progressBlock('File links to your favorite editor');

        if (! File::exists('.env')) {
            $this->errorProgressBlock();
            $this->note('.ENV file does not exist', 'red');

            return;
        }

        $envFileContent = Str::of(File::get('.env'));

        if ($envFileContent->contains('DEVTOOLS_EDITOR')) {
            $this->closeProgressBlock('Not needed', 'yellow');
            $this->note('DevTools editor is already on .env file.');

            return;
        }

        $this->newLine();
        $this->note('Note that for file links to work, your app must have a URL handler set up.');
        $this->note('More info at <fg=blue>https://backpackforlaravel.com/products/devtools/troubleshooting.md</>.');
        $this->note('');

        // create chunks of 8 entries, first chunk is 6
        $editorsChunks = collect(self::EDITORS)->slice(6)->chunk(8)
            ->prepend(collect(self::EDITORS)->slice(0, 6));

        $editorsChunks
            ->map(function ($chunk, $i) use ($editorsChunks) {
                $last = $i === $editorsChunks->count() - 1;
                $note = sprintf('<fg=blue>%s</>', $chunk->join('</>, <fg=blue>'));

                if ($i === 0) {
                    $note = "Choose between: $note";
                }

                $this->note($last ? "$note." : "$note,");
            });

        $this->askForEditor();
    }

    public function askForEditor(): void
    {
        $editor = $this->ask(' What editor to use for opening file links?', 'none');
        $this->deleteLines(3);

        if ($editor !== 'none') {
            $this->handleSetupEditorChosen($editor);
        } else {
            $this->deleteLines(7 + ($this->editorError ?? 0) * 2);
            $this->progressBlock('File links to your favorite editor');
            $this->closeProgressBlock('skipped', 'blue');
            $this->note('DevTools default editor skipped.');
        }
    }

    public function handleSetupEditorChosen(?string $editorResponse): void
    {
        if (! in_array($editorResponse, self::EDITORS)) {
            $this->deleteLines(1 + ($this->editorError ?? 0) * 2);
            $this->editorError = true;

            $this->note('');
            $this->note("The editor '$editorResponse' is not valid. Let's try again!", 'red');
            $this->newLine();
            $this->askForEditor();

            return;
        }

        File::append('.env', PHP_EOL.'DEVTOOLS_EDITOR='.$editorResponse);

        $this->deleteLines(7 + ($this->editorError ?? 0) * 2);
        $this->progressBlock('File links to your favorite editor');
        $this->closeProgressBlock();
        $this->note("Successfully added <fg=blue>$editorResponse</> to .env file as DevTools default editor.");
    }
}
