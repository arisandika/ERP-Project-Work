<?php

namespace App\Livewire\Breezy;

use Filament\Facades\Filament;
use Filament\Forms;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo as BasePersonalInfo;

class PersonalInfo extends BasePersonalInfo
{
    protected function getProfileFormComponents(): array
    {
        $components = [
            $this->getNameComponent(),
        ];

        if (Filament::auth()->user()?->hasRole('super_admin')) {
            $components[] = $this->getEmailComponent();
        }

        return $components;
    }

    public function mount(): void
    {
        parent::mount();

        if (! Filament::auth()->user()?->hasRole('super_admin')) {
            $this->only = ['name'];

            if ($this->hasAvatars) {
                $this->only[] = filament('filament-breezy')
                    ->getAvatarUploadComponent()
                    ->getStatePath(false);
            }

            $this->form->fill($this->user->only($this->only));
        }
    }
}