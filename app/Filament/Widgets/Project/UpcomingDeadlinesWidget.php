<?php
namespace App\Filament\Widgets\Project;

use App\Models\Project\Project;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class UpcomingDeadlinesWidget extends BaseWidget
{
    protected static ?string $heading = 'Project Mendekati Deadline';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->whereBetween('end_date', [now(), now()->addDays(14)])
                    ->orderBy('end_date')
            )
            ->columns([
                Tables\Columns\ColorColumn::make('color')->label(''),
                Tables\Columns\TextColumn::make('name')
                    ->label('Project')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('projectManager.full_name')
                    ->label('PM')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->badge()
                    ->color(fn($state) => $state >= 75 ? 'success' : ($state >= 40 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Deadline')
                    ->dateTime('d M Y'),
                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Sisa Hari')
                    ->badge()
                    ->color(fn(Project $record) => $record->remaining_days <= 3 ? 'danger' : 'warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn(Project $record) => route('filament.admin.resources.project.projects.view', $record)),
            ])
            ->paginated(false)
            ->emptyStateHeading('Tidak ada deadline dalam 14 hari ke depan');
    }
}
