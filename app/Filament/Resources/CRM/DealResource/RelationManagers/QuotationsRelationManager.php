<?php

namespace App\Filament\Resources\DealResource\RelationManagers;

use App\Filament\Resources\Sales\QuotationResource;
use App\Mail\QuotationSent;
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

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Lead')
                    ->state(function (Quotation $record) {
                        $deal = $record->deal;
                        if (!$deal)
                            return '-';
                        if ($deal->customer)
                            return $deal->customer->name . ' (Customer)';
                        if ($deal->lead)
                            return $deal->lead->name . ' (Lead)';
                        return '-';
                    })
                    ->description(fn(Quotation $record) => $record->deal?->deal_number)
                    ->searchable(['deal.customer.name', 'deal.lead.name'])
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('PIC (Sales)')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('Tanggal Penawaran')
                    ->date('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berlaku Hingga')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function (Quotation $record) {
                        if (in_array($record->status, ['accepted', 'rejected'])) {
                            return 'gray';
                        }
                        if (\Carbon\Carbon::parse($record->valid_until)->isPast()) {
                            return 'danger';
                        }
                        if (\Carbon\Carbon::parse($record->valid_until)->diffInDays(now()) <= 3) {
                            return 'warning';
                        }
                        return 'success';
                    })
                    ->description(function (Quotation $record) {
                        if (in_array($record->status, ['accepted', 'rejected']))
                            return null;

                        $days = now()->diffInDays(\Carbon\Carbon::parse($record->valid_until), false);
                        if ($days < 0)
                            return 'Expired ' . abs(intval($days)) . ' hari lalu';
                        if ($days == 0)
                            return 'Hari ini terakhir';
                        return 'Sisa ' . intval($days) . ' hari';
                    }),

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
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'success' : 'warning')
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\SelectFilter::make('nx_employee_id')
                    ->label('PIC (Sales)')
                    ->relationship('employee', 'full_name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_expired')
                    ->label('Status Kedaluwarsa')
                    ->placeholder('Semua Penawaran')
                    ->trueLabel('Sudah Expired')
                    ->falseLabel('Masih Berlaku')
                    ->queries(
                        true: fn(Builder $query) => $query->whereDate('valid_until', '<', now())->whereIn('status', ['draft', 'sent']),
                        false: fn(Builder $query) => $query->whereDate('valid_until', '>=', now())->orWhereNotIn('status', ['draft', 'sent']),
                    ),

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

                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Quotation $record) {
                        $emailTarget = $record->deal?->customer?->email ?? $record->deal?->lead?->email;

                        if (!$emailTarget) {
                            Notification::make()
                                ->title('Email Klien tidak tersedia!')
                                ->danger()
                                ->send();
                            return;
                        }

                        Mail::to($emailTarget)->send(new QuotationSent($record));

                        $record->update(['status' => 'sent']);

                        Notification::make()
                            ->title('Penawaran berhasil dikirim!')
                            ->body("Terkirim ke: {$emailTarget}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('create_new_quotation')
                    ->label('Buat Penawaran')
                    ->color('primary')
                    ->url(fn(RelationManager $livewire): string => QuotationResource::getUrl('create', [
                        'nx_deal_id' => $livewire->getOwnerRecord()->id
                    ]))
                    ->openUrlInNewTab()
            ]);
    }
}