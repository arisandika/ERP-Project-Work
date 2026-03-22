<?php

namespace App\Filament\Resources\Procurement\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Finance\FinancialRecord;
use App\Models\Procurement\PurchaseOrder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments'; // Pastikan Model PurchaseOrder Anda punya relasi hasMany('payments')
    protected static ?string $title = 'Riwayat Pembayaran ke Supplier';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment_number')
                    ->label('No. Pembayaran')
                    ->default(fn () => 'PAY-PO-' . strtoupper(uniqid()))
                    ->required()
                    ->maxLength(255),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('Tanggal Transfer')
                    ->default(now())
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Dibayar')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(1),

                Forms\Components\Select::make('payment_method')
                    ->label('Metode Transfer')
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
                Tables\Columns\TextColumn::make('payment_number')->label('No. Bayar'),
                Tables\Columns\TextColumn::make('payment_date')->label('Tanggal')->date('d M Y'),
                Tables\Columns\TextColumn::make('amount')->label('Jumlah')->money('IDR', true)->weight('bold')->color('danger'),
                Tables\Columns\TextColumn::make('payment_method')->label('Metode'),
                Tables\Columns\TextColumn::make('notes')->label('Catatan'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Bayar Tagihan Supplier')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('primary')
                    ->after(function ($record, RelationManager $livewire) {
                        $po = $livewire->ownerRecord;

                        // 1. CATAT KE BUKU BESAR (Uang Kas Keluar)
                        FinancialRecord::create([
                            'transaction_date' => $record->payment_date,
                            'type'             => 'pengeluaran',
                            'amount'           => $record->amount,
                            'category'         => 'Purchase Order',
                            'description'      => 'Pembayaran PO ke Supplier: ' . ($po->supplier->name ?? '-') . ' via ' . strtoupper($record->payment_method),
                            'reference_number' => $record->payment_number,
                            'reference_type'   => PurchaseOrder::class,
                            'reference_id'     => $po->id,
                            'created_by'       => auth()->id() ?? 1,
                        ]);

                        // Opsional: Buat fungsi di Model PurchaseOrder untuk menghitung remaining_balance dan auto-update status "Lunas"
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
