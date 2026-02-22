<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;

class ExportTicketsAction
{
    public static function make(): Action
    {
        return Action::make('export_tickets')
            ->label('Export Tickets')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('success')
            ->form([
                Section::make('Pilih Kolom Ekspor')
                    ->description('Tentukan kolom apa saja yang akan dimasukkan ke dalam file Excel')
                    ->schema([
                        CheckboxList::make('columns')
                            ->label('Columns')
                            ->options([
                                'uuid' => 'Ticket ID',
                                'name' => 'Title',
                                'description' => 'Description',
                                'status' => 'Status',
                                'assignee' => 'Assignee',
                                'project' => 'Project',
                                'epic' => 'Epic',
                                'due_date' => 'Due Date',
                                'created_at' => 'Created At',
                                'updated_at' => 'Updated At',
                            ])
                            ->default(['uuid', 'name', 'status', 'assignee', 'due_date', 'created_at'])
                            ->required()
                            ->minItems(1)
                            ->columns(2)
                            ->gridDirection('row')
                    ])
            ])
            ->action(function (array $data, $livewire): void {
                $livewire->exportTickets($data['columns'] ?? []);
            });
    }
}