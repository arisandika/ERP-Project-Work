<?php

namespace App\Filament\Resources\Project\ProjectResource\Pages;

use App\Filament\Pages\Project\ProjectBoard;
use App\Filament\Resources\Project\ProjectResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')
                ->label('Project Board')
                ->icon('heroicon-o-view-columns')
                ->color('warning')
                ->url(fn() => ProjectBoard::getUrl(['project_id' => $this->record->id])),

            Action::make('external_access')
                ->label('External Dashboard')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                // ->visible(fn() => auth()->user()->hasRole('super_admin'))
                ->modalHeading('Akses External Dashboard')
                ->modalDescription('Bagikan kredensial ini ke user eksternal atau client untuk mengakses dashboard project ini')
                ->modalContent(function () {
                    $record = $this->record;
                    $externalAccess = $record->externalAccess;

                    if (!$externalAccess) {
                        $externalAccess = $record->generateExternalAccess();
                    }

                    $dashboardUrl = url('/external/' . $externalAccess->access_token);

                    return view('filament.components.external-access-modal', [
                        'dashboardUrl' => $dashboardUrl,
                        'password' => $externalAccess->password,
                        'lastAccessed' => $externalAccess->last_accessed_at ? $externalAccess->last_accessed_at->format('d M Y H:i') : null,
                        'isActive' => $externalAccess->is_active,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Project';
    }
}
