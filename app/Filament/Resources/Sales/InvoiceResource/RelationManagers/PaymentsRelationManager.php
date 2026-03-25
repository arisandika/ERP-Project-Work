<?php

namespace App\Filament\Resources\Sales\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Finance\FinancialRecord;
use App\Models\Sales\Invoice;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat Pembayaran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment_number')
                    ->label('No. Pembayaran')
                    ->default('AUTO-GENERATED') // UBAH INI
                    ->disabled()                // TAMBAH INI
                    ->dehydrated()              // TAMBAH INI
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
        // (Isi tabel tetap sama seperti punyamu)
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')->label('No. Pembayaran')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('payment_date')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('Jumlah')->money('IDR', true)->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'transfer' => 'info',
                        'cash' => 'success',
                        'credit_card' => 'warning',
                        'qris' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'transfer' => 'Transfer Bank',
                        'cash' => 'Tunai',
                        'credit_card' => 'Kartu Kredit',
                        'qris' => 'QRIS',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('notes')->label('Catatan')->limit(30),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Catat Pembayaran')
                    ->after(function ($record, RelationManager $livewire) {
                        $invoice = $livewire->ownerRecord;

                        FinancialRecord::create([
                            'transaction_date' => $record->payment_date,
                            'type'             => 'pemasukan',
                            'amount'           => $record->amount,
                            'category'         => 'Sales Revenue',
                            'description'      => 'Pembayaran Invoice dari Klien: ' . ($invoice->customer->name ?? '-') . ' via ' . strtoupper($record->payment_method),
                            // UBAH reference_number INI:
                            'reference_number' => $record->payment_number,
                            'reference_type'   => Invoice::class,
                            'reference_id'     => $invoice->id,
                            'created_by'       => auth()->id() ?? 1,
                        ]);

                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
