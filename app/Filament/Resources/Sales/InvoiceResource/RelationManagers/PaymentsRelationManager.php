<?php

namespace App\Filament\Resources\Sales\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat Pembayaran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('payment_number_placeholder')
                    ->label('No. Pembayaran')
                    ->content('Auto Generate'),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Dibayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0.01)
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->remaining_balance)
                    ->maxValue(fn (RelationManager $livewire) => $livewire->ownerRecord->remaining_balance),

                Forms\Components\Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'transfer' => 'Transfer Bank',
                        'cash' => 'Tunai',
                        'credit_card' => 'Kartu Kredit',
                        'qris' => 'QRIS',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR', true)
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'transfer' => 'info',
                        'cash' => 'success',
                        'credit_card' => 'warning',
                        'qris' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'transfer' => 'Transfer Bank',
                        'cash' => 'Tunai',
                        'credit_card' => 'Kartu Kredit',
                        'qris' => 'QRIS',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'settlement', 'success' => 'success',
                        'pending' => 'warning',
                        'failed', 'cancelled', 'expired' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Catat Pembayaran')
                    ->using(function (array $data, RelationManager $livewire) {
                        return DB::transaction(function () use ($data, $livewire) {
                            $invoice = $livewire->ownerRecord;

                            return $invoice->payments()->create([
                                'payment_date' => $data['payment_date'],
                                'amount' => (float) $data['amount'],
                                'payment_method' => $data['payment_method'],
                                'status' => 'paid',
                                'notes' => $data['notes'] ?? null,
                                'created_by' => auth()->id() ?? 1,
                            ]);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
