<?php

namespace App\Filament\Resources\Project\TicketResource\Pages;

use App\Filament\Resources\Project\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string
    {
        return 'Edit Ticket';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
