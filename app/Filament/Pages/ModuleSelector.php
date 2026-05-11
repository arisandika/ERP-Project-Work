<?php

namespace App\Filament\Pages;

use App\Support\ModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ModuleSelector extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.pages.module-selector';

    protected static ?string $title = 'Pilih Modul';

    protected static ?string $slug = 'modules';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        // Setiap masuk halaman pilih modul, kosongkan dulu active module.
        // Jadi sidebar module lama tidak kebawa.
        // ModuleAccess::forgetActive();
    }

    public function getModules(): Collection
    {
        return ModuleAccess::availableModules();
    }

    public function selectModule(string $moduleKey): void
    {
        $selectedModule = $this->getModules()->firstWhere('key', $moduleKey);

        abort_if(! $selectedModule, 403);

        ModuleAccess::setActive($selectedModule['key']);

        $routeName = $selectedModule['route'] ?? null;

        if ($routeName && app('router')->has($routeName)) {
            $this->redirect(route($routeName));

            return;
        }

        $this->redirect(filament()->getCurrentPanel()->getUrl());
    }
}
