<?php

namespace App\Filament\Resources\Project\TicketPriorityResource\Pages;

use App\Filament\Resources\Project\TicketPriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTicketPriority extends ListRecords
{
    protected static string $resource = TicketPriorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Prioritas Ticket'),
        ];
    }
}
