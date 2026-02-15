<?php

namespace App\Filament\Resources\Project\TicketPriorityResource\Pages;

use App\Filament\Resources\Project\TicketPriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicketPriority extends EditRecord
{
    protected static string $resource = TicketPriorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Prioritas Ticket';
    }
}
