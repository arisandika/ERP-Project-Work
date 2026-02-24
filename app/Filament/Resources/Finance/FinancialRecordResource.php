<?php

namespace App\Filament\Resources\Finance;

use App\Filament\Resources\Finance\FinancialRecordResource\Pages;
use App\Models\Finance\FinancialRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;

class FinancialRecordResource extends Resource
{
    protected static ?string $model = FinancialRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    // Grouping Menu biar rapi di sidebar
    protected static ?string $navigationGroup = 'Manajemen Finance';

    // Label Menu
    protected static ?string $navigationLabel = 'Catatan Operasional';

    // Urutan menu (opsional, biar dibawah Laporan)
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Input Transaksi')
                    ->description('Catat pengeluaran kecil atau pemasukan non-penjualan.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('transaction_date')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->required()
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar-days'),

                                Forms\Components\Select::make('type')
                                    ->label('Jenis Transaksi')
                                    ->options([
                                        'pemasukan' => 'Pemasukan (Uang Masuk)',
                                        'pengeluaran' => 'Pengeluaran (Uang Keluar)',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\TextInput::make('description')
                            ->label('Keterangan')
                            ->placeholder('Contoh: Beli Bensin, Bayar Listrik')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal')
                                    ->numeric()
                                    ->prefix('IDR')
                                    ->required(),

                                Forms\Components\Select::make('category')
                                    ->label('Kategori Akun')
                                    ->options([
                                        'Operasional' => 'Biaya Operasional (Listrik/Air)',
                                        'Gaji' => 'Gaji Karyawan',
                                        'Perlengkapan' => 'ATK & Perlengkapan',
                                        'Lainnya' => 'Lain-lain',
                                    ])
                                    ->default('Operasional')
                                    ->required(),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialRecords::route('/'),
            'create' => Pages\CreateFinancialRecord::route('/create'),
            'edit' => Pages\EditFinancialRecord::route('/{record}/edit'),
        ];
    }
}
