<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseReturnResource\Pages;
use App\Models\Procurement\PurchaseReturn;
use App\Services\Finance\PurchaseReturnFinancialService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use App\Filament\Concerns\BelongsToModule;

class PurchaseReturnResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'procurement';
    protected static ?string $model = PurchaseReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?string $pluralModelLabel = 'Retur Pembelian';
    protected static ?string $slug = 'procurement/purchase-returns';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // 1. HEADER SECTION
            Forms\Components\Section::make('Informasi Dokumen Retur')
                ->description('Lengkapi detail informasi utama terkait pengembalian barang ke supplier.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    // Baris 1: Nomor Retur (1 Kolom) & Judul Retur (2 Kolom)
                    Forms\Components\TextInput::make('return_number')
                        ->label('Nomor Retur')
                        ->default(fn () => \App\Models\Procurement\PurchaseReturn::previewNextReturnNumber())
                        ->disabled()
                        ->dehydrated(false)
                        ->prefixIcon('heroicon-o-hashtag')
                        ->helperText('Sistem akan mengunci nomor final saat disimpan.')
                        ->required()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('title')
                        ->label('Nama / Judul Retur')
                        ->placeholder('Contoh: Retur Laptop Rusak Layar Batch 1')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->extraInputAttributes(['class' => 'text-xl font-bold border-t-0 border-l-0 border-r-0 border-b-2 border-gray-300 focus:ring-0 px-0 bg-transparent']),

                    // Baris 2: Supplier, Tanggal, & Penyelesaian
                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-o-building-storefront')
                        ->required()
                        ->columnSpan(1),

                    Forms\Components\DatePicker::make('return_date')
                        ->label('Tanggal Retur')
                        ->default(now())
                        ->required()
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->native(false)
                        ->columnSpan(1),

                    Forms\Components\Select::make('resolution_type')
                        ->label('Tipe Penyelesaian')
                        ->options([
                            'credit_note' => 'Potong Hutang Usaha',
                            'refund' => 'Refund Dana',
                        ])
                        ->required()
                        ->prefixIcon('heroicon-o-arrow-path-rounded-square')
                        ->helperText('Cara supplier mengganti retur ini.')
                        ->columnSpan(1),

                    // Baris 3: Catatan
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->columnSpanFull(),
                ])->columns(3), // <-- Diubah menjadi 3 kolom agar rapi

            // 2. DETAIL SECTION (ITEMS)
            Forms\Components\Section::make('Item yang Diretur')
                ->description('Daftar spesifik barang yang akan dikembalikan beserta alasannya.')
                ->icon('heroicon-o-archive-box-x-mark')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->addActionLabel('Tambah Produk Retur')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Produk')
                                ->relationship('product', 'product_name')
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-o-cube')
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Qty')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->prefixIcon('heroicon-o-scale')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('reason')
                                ->label('Alasan Retur')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-chat-bubble-bottom-center-text')
                                ->columnSpan(3),
                        ])
                        ->columns(8)
                        ->defaultItems(1)
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            return $data;
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label('No. Retur')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag')
                    ->weight('bold'),

                // Menambahkan Kolom Title di Tabel
                Tables\Columns\TextColumn::make('title')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->icon('heroicon-o-building-storefront')
                    ->searchable(),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-o-calendar')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolution_type')
                    ->label('Penyelesaian')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'credit_note' => 'heroicon-o-document-minus',
                        'refund' => 'heroicon-o-banknotes',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'credit_note' => 'Potong Hutang',
                        'refund' => 'Refund',
                        default => 'Belum Ditentukan'
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match (strtolower($state)) {
                        'draft' => 'heroicon-o-pencil',
                        'approved' => 'heroicon-o-check-badge',
                        'shipped' => 'heroicon-o-truck',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'approved' => 'info',
                        'shipped' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    // Mengubah string status asli menjadi Kapital
                    ->formatStateUsing(fn(string $state) => strtoupper($state)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->hidden(fn (PurchaseReturn $record): bool => $record->status !== 'draft'),

                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-s-eye')
                    ->iconButton(),

                // MENGGANTI TOMBOL APPROVE MENJADI KIRIM BARANG (SHIPPED)
                Tables\Actions\Action::make('ship_return')
                    ->label('Kirim')
                    ->tooltip('Kirim Barang ke Supplier')
                    ->icon('heroicon-s-truck') // Ikon Solid Truk
                    ->color('warning')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Retur ke Supplier')
                    ->modalDescription('Apakah barang fisik sudah diserahkan ke kurir / supplier? Status akan diubah menjadi SHIPPED.')
                    ->visible(fn (PurchaseReturn $record): bool => in_array($record->status, ['draft', 'approved']))
                    ->action(function (PurchaseReturn $record) {
                        $record->update(['status' => 'shipped']);
                        Notification::make()->title('Status Berubah: Barang Dikirim')->success()->send();
                    }),

                // TOMBOL SELESAIKAN (COMPLETE)
                Tables\Actions\Action::make('complete')
                    ->label('Selesai')
                    ->tooltip('Selesaikan & Perbarui Keuangan')
                    ->icon('heroicon-s-currency-dollar') // Ikon Solid Uang
                    ->color('success')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Retur Pembelian')
                    ->modalDescription('Tindakan ini akan memicu pembaruan pada modul Akuntansi (Hutang terpotong / Kas bertambah). Lanjutkan?')
                    ->visible(fn (PurchaseReturn $record): bool => in_array($record->status, ['approved', 'shipped']))
                    ->action(function (PurchaseReturn $record): void {
                        try {
                            app(PurchaseReturnFinancialService::class)->execute($record);

                            Notification::make()
                                ->title('Retur Berhasil Diselesaikan')
                                ->body('Data keuangan telah diperbarui secara otomatis.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Log::error('Gagal menyelesaikan retur: ' . $e->getMessage());

                            Notification::make()
                                ->title('Gagal Memproses Transaksi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
