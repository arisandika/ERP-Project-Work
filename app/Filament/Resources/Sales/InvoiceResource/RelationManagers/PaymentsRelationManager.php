<?php

namespace App\Filament\Resources\Sales\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// --- REVISI JURNAL FINANCE ---
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
                    ->default(fn () => 'PAY-' . strtoupper(uniqid())) // Auto-generate contoh sederhana
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
                    // Set default-nya adalah sisa tagihan agar kasir tidak capek ngetik
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

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // --- REVISI JURNAL FINANCE: Saat tombol Tambah Pembayaran diklik ---
                Tables\Actions\CreateAction::make()
                    ->label('Catat Pembayaran')
                    ->after(function ($record, RelationManager $livewire) {
                        // $record adalah data Payment yang baru saja diinput
                        // $ownerRecord adalah data Invoice tempat Relation ini menempel
                        $invoice = $livewire->ownerRecord;

                        // 1. Tembak data uang masuk ke Buku Kas Utama (FinancialRecord)
                        FinancialRecord::create([
                            'transaction_date' => $record->payment_date,
                            'type'             => 'pemasukan',
                            'amount'           => $record->amount,
                            'category'         => 'Sales Revenue',
                            'description'      => 'Pembayaran Invoice dari Klien: ' . ($invoice->customer->name ?? '-') . ' via ' . strtoupper($record->payment_method),
                            'reference_number' => $invoice->invoice_number, // Referensi ke nomor invoice
                            'reference_type'   => Invoice::class,           // Polymorphic relation
                            'reference_id'     => $invoice->id,
                            'created_by'       => auth()->id() ?? 1,
                        ]);

                        // 2. Trigger fungsi recalculateStatus() yang ada di Model Invoice Anda
                        // agar statusnya otomatis berubah jadi 'paid' atau 'partial'
                        $invoice->recalculateStatus();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Sebaiknya fitur Edit/Delete di Payment dimatikan untuk mencegah Fraud akuntansi.
                // Jika kasir salah catat nominal, uang di FinancialRecord jadi tidak sinkron.
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
