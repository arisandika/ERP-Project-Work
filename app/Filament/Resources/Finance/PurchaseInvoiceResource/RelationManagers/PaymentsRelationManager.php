<?php

namespace App\Filament\Resources\Procurement\PurchaseInvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Finance\FinancialRecord;
use App\Models\Procurement\PurchaseInvoice;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Riwayat Pembayaran Tagihan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment_number')
                    ->label('No. Pembayaran')
                    ->default(fn () => 'PAY-PI-' . strtoupper(uniqid()))
                    ->required()
                    ->maxLength(255),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(now())
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Dibayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    // Default dan Maksimal diset otomatis ke sisa tagihan (remaining balance)
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->remaining_balance)
                    ->maxValue(fn (RelationManager $livewire) => $livewire->ownerRecord->remaining_balance)
                    ->minValue(1),

                Forms\Components\Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'transfer_bca' => 'Transfer Bank BCA',
                        'transfer_mandiri' => 'Transfer Bank Mandiri',
                        'cash' => 'Tunai (Kas Kecil)',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan (No. Referensi Bank)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')->label('No. Bayar')->searchable(),
                Tables\Columns\TextColumn::make('payment_date')->label('Tanggal')->date('d M Y'),
                Tables\Columns\TextColumn::make('amount')->label('Jumlah')->money('IDR', true)->weight('bold')->color('danger'),
                Tables\Columns\TextColumn::make('payment_method')->label('Metode')
                    ->formatStateUsing(fn(string $state) => ucwords(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('notes')->label('Catatan'),
            ])
            ->headerActions([
                // Tombol bayar hanya muncul jika tagihan belum lunas
                Tables\Actions\CreateAction::make()
                    ->label('Bayar Tagihan')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->visible(fn (RelationManager $livewire) => in_array($livewire->ownerRecord->status, ['unpaid', 'partial']))
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
