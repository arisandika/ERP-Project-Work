<?php
namespace App\Filament\Widgets\CRM;

use App\Filament\Resources\CRM\LeadResource;
use App\Models\CRM\Lead;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class StaleLeadsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Lead Belum Ditindaklanjuti (>7 Hari)';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()
                    ->where('status', Lead::STATUS_NEW)
                    ->where('created_at', '<=', now()->subDays(7))
                    ->orderBy('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lead')
                    ->weight('semibold')
                    ->description(fn(Lead $record) => $record->customer_type === 'company' ? 'Perusahaan' : 'Individu'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->description(fn(Lead $record) => $record->email)
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->formatStateUsing(fn(string $state) => ucwords(str_replace('_', ' ', $state)))
                    ->badge(),
                Tables\Columns\TextColumn::make('createdBy.full_name')
                    ->label('Sales')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Umur Lead')
                    ->getStateUsing(fn(Lead $record) => $record->created_at->diffInDays(now()) . ' Hari')
                    ->badge()
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('follow_up')
                    ->label('Follow Up')
                    ->icon('heroicon-o-phone')
                    ->color('warning')
                    ->url(fn(Lead $record) => LeadResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50)
            ->emptyStateHeading('Semua lead baru sudah ditindaklanjuti');
    }
}
