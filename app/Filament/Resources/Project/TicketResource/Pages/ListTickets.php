<?php

namespace App\Filament\Resources\Project\TicketResource\Pages;

use App\Filament\Resources\Project\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Ticket'),
        ];
    }
}
