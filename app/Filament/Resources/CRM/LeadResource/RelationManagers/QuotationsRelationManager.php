<?php

namespace App\Filament\Resources\CRM\LeadResource\RelationManagers;

use App\Filament\Resources\Sales\QuotationResource;
use App\Mail\QuotationSent;
use App\Models\CRM\Lead;
use App\Models\Sales\Quotation;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class QuotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations';

    protected static ?string $title = 'Daftar Penawaran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('No. Penawaran')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('lead.name')
                    ->label('Lead')
                    ->sortable()
                    ->searchable()
                    ->description(fn(Quotation $record) => $record->lead?->email),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR', true)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        if (!$record->lead || !$record->lead->email) {
                            Notification::make()
                                ->title('Email lead tidak tersedia!')
                                ->danger()
                                ->send();
                            return;
                        }
                        Mail::to($record->lead->email)
                            ->send(new QuotationSent($record));
                        $record->update(['status' => 'sent']);
                        Notification::make()
                            ->title('Penawaran berhasil dikirim ke Lead')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('edit_quotation')
                    ->label('Detail Penawaran')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(
                        fn(Quotation $record): string =>
                        QuotationResource::getUrl('edit', ['record' => $record])
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
