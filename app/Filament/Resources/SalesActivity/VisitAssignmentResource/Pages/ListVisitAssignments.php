<?php

namespace App\Filament\Resources\SalesActivity\VisitAssignmentResource\Pages;

use App\Filament\Resources\SalesActivity\VisitAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitAssignments extends ListRecords
{
    protected static string $resource = VisitAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
