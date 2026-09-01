<?php

namespace App\Filament\Resources\HR\EmployeePerformanceResource\RelationManagers;

use App\Models\HR\Employee;
use App\Models\HR\PerformanceEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PerformanceEvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'performanceEvaluations';

    protected static ?string $title = 'Penilaian Kinerja';

    protected static ?string $modelLabel = 'Penilaian';

    protected static ?string $pluralModelLabel = 'Penilaian Kinerja';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->performanceEvaluations->count();
    }

    protected function ratingOptions(): array
    {
        return [
            5 => '5 - Outstanding',
            4 => '4 - Good',
            3 => '3 - Average',
            2 => '2 - Poor',
            1 => '1 - Very Poor',
        ];
    }

    public function form(Form $form): Form
    {
        $evaluatorId = auth()->user()->employee?->id;

        return $form
            ->schema([
                Forms\Components\Select::make('evaluator_id')
                    ->label('Penilai')
                    ->relationship('evaluator', 'full_name')
                    ->default($evaluatorId)
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('period')
                    ->label('Periode')
                    ->required()
                    ->placeholder('cth: 2025-Q4, 2025-12')
                    ->maxLength(32),
                Forms\Components\Radio::make('rating')
                    ->label('Overall Rating')
                    ->required()
                    ->options($this->ratingOptions())
                    ->inline()
                    ->default(3),
                Forms\Components\Radio::make('quality')
                    ->label(PerformanceEvaluation::CRITERIA['quality'])
                    ->options($this->ratingOptions())
                    ->inline()
                    ->default(3),
                Forms\Components\Radio::make('teamwork')
                    ->label(PerformanceEvaluation::CRITERIA['teamwork'])
                    ->options($this->ratingOptions())
                    ->inline()
                    ->default(3),
                Forms\Components\Radio::make('communication')
                    ->label(PerformanceEvaluation::CRITERIA['communication'])
                    ->options($this->ratingOptions())
                    ->inline()
                    ->default(3),
                Forms\Components\Radio::make('problem_solving')
                    ->label(PerformanceEvaluation::CRITERIA['problem_solving'])
                    ->options($this->ratingOptions())
                    ->inline()
                    ->default(3),
                Forms\Components\DatePicker::make('evaluated_at')
                    ->label('Tanggal Penilaian')
                    ->default(now())
                    ->required()
                    ->displayFormat('d M Y')
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar-days'),
                Forms\Components\Textarea::make('feedback')
                    ->label('Comments / Notes')
                    ->maxLength(2000)
                    ->rows(3)
                    ->placeholder('Masukkan feedback evaluasi...'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period')
            ->heading('Penilaian Kinerja')
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('evaluator.full_name')
                    ->label('Penilai')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('rating')
                    ->label('Overall')
                    ->colors([
                        'success' => fn($state) => $state >= 4,
                        'warning' => fn($state) => $state == 3,
                        'danger'  => fn($state) => $state <= 2,
                    ])
                    ->formatStateUsing(fn($state) => ($state) . ' - ' . (PerformanceEvaluation::RATINGS[$state] ?? '')),
                Tables\Columns\BadgeColumn::make('quality')
                    ->label(PerformanceEvaluation::CRITERIA['quality'])
                    ->colors([
                        'success' => fn($state) => $state >= 4,
                        'warning' => fn($state) => $state == 3,
                        'danger'  => fn($state) => $state <= 2,
                    ])
                    ->formatStateUsing(fn($state) => ($state) . ' - ' . (PerformanceEvaluation::RATINGS[$state] ?? '—'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('teamwork')
                    ->label(PerformanceEvaluation::CRITERIA['teamwork'])
                    ->colors([
                        'success' => fn($state) => $state >= 4,
                        'warning' => fn($state) => $state == 3,
                        'danger'  => fn($state) => $state <= 2,
                    ])
                    ->formatStateUsing(fn($state) => ($state) . ' - ' . (PerformanceEvaluation::RATINGS[$state] ?? '—'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('communication')
                    ->label(PerformanceEvaluation::CRITERIA['communication'])
                    ->colors([
                        'success' => fn($state) => $state >= 4,
                        'warning' => fn($state) => $state == 3,
                        'danger'  => fn($state) => $state <= 2,
                    ])
                    ->formatStateUsing(fn($state) => ($state) . ' - ' . (PerformanceEvaluation::RATINGS[$state] ?? '—'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('problem_solving')
                    ->label(PerformanceEvaluation::CRITERIA['problem_solving'])
                    ->colors([
                        'success' => fn($state) => $state >= 4,
                        'warning' => fn($state) => $state == 3,
                        'danger'  => fn($state) => $state <= 2,
                    ])
                    ->formatStateUsing(fn($state) => ($state) . ' - ' . (PerformanceEvaluation::RATINGS[$state] ?? '—'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('feedback')
                    ->label('Comments / Notes')
                    ->limit(80)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('evaluated_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period')
                    ->label('Periode')
                    ->relationship('period', 'period')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Penilaian')
                    ->color('success')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('evaluated_at', 'desc');
    }
}
