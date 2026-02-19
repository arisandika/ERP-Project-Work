<?php

namespace App\Filament\Resources\Project\TicketPriorityResource\Pages;

use App\Filament\Resources\Project\TicketPriorityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTicketPriority extends CreateRecord
{
    protected static string $resource = TicketPriorityResource::class;

    public function getTitle(): string
    {
        return 'Tambah Prioritas Ticket';
    }
}
