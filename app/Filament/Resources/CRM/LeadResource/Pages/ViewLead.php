<?php

namespace App\Filament\Resources\CRM\LeadResource\Pages;

use App\Filament\Resources\CRM\LeadResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->url(static::getResource()::getUrl()) 
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Lead';
    }
}
