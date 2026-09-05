<?php

namespace App\Filament\Resources\AfterSales;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;
use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Closure;

class ReturnRequestResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'inventory';
    protected static ?string $model = ReturnRequest::class;
    // Konfigurasi Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationGroup = 'Manajemen After-Sales';
    protected static ?string $navigationLabel = 'Penerimaan Return';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tiket Penerimaan Return')
                    ->description('Catat barang masuk dari klien dan tentukan rute garansinya.')
                    ->schema([
                        Forms\Components\TextInput::make('rma_number')
                            ->label('No. RMA')
                            // Generate default value, namun biasanya ini ditangani di observer/boot model
                            ->default(fn() => 'RMA/' . date('Ym') . '/' . rand(1000, 9999))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('serial_number_id')
                            ->label('Scan SN Produk')
                            // 1. FILTER UI: Hanya memuat SN yang statusnya sudah terjual
                            ->relationship(
                                name: 'serialNumber',
                                titleAttribute: 'serial_number',
                                modifyQueryUsing: fn(Builder $query) => $query->where('status', SerialNumber::STATUS_SOLD)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            // 2. BACKEND VALIDATION: Mencegah manipulasi payload API
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, Closure $fail) {
                                        $sn = SerialNumber::find($value);
                                        if (!$sn || $sn->status !== SerialNumber::STATUS_SOLD) {
                                            $fail('Serial Number ini belum tercatat keluar dari gudang (Belum Terjual). Retur ditolak.');
                                        }
                                    };
                                },
                            ])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    $set('customer_id', null);
                                    return;
                                }

                                $sn = SerialNumber::find($state);
                                if ($sn && $sn->customer_id) {
                                    $set('customer_id', $sn->customer_id);
                                }
                            }),
                        Forms\Components\Select::make('customer_id')
                            ->label('Klien')
                            ->relationship('customer', 'name')
                            ->disabled()  // Di-disable karena terisi otomatis dari SN
                            ->dehydrated()  // Memastikan data tetap terkirim saat disubmit
                            ->required(),
                        Forms\Components\Select::make('warranty_type')
                            ->label('Rute Garansi (Warranty Route)')
                            ->options([
                                'supplier' => 'Garansi Supplier / Distributor (Kirim Eksternal)',
                                'store' => 'Garansi Toko (Service Internal)',
                            ])
                            ->required()
                            ->native(false)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('issue_type')
                            ->label('Jenis Kendala')
                            ->options(ReturnRequest::getIssueTypeLabels())
                            ->nullable()
                            ->native(false)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('issue_description')
                            ->label('Detail Kerusakan (Keluhan)')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rma_number')
                    ->label('No. RMA')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Klien')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issue_type')
                    ->label('Jenis Kendala')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReturnRequest::getIssueTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('warranty_type')
                    ->label('Rute Garansi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'supplier' => 'warning',
                        'store' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => ReturnRequest::STATUS_SUBMITTED,
                        'blue' => ReturnRequest::STATUS_RECEIVED,
                        'warning' => ReturnRequest::STATUS_SENT_TO_VENDOR,
                        'danger' => ReturnRequest::STATUS_INTERNAL_REPAIR,
                        'info' => ReturnRequest::STATUS_READY_FOR_RETURN,
                        'success' => ReturnRequest::STATUS_RETURNED_TO_CLIENT,
                        'destructive' => ReturnRequest::STATUS_REJECTED,
                    ])
                    ->formatStateUsing(fn(string $state): string => ReturnRequest::getStatusLabels()[$state] ?? $state),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Terima barang dari klien (submitted -> received)
                Tables\Actions\Action::make('receive_from_client')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn($record) => $record->status === ReturnRequest::STATUS_SUBMITTED)
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->update([
                        'status' => ReturnRequest::STATUS_RECEIVED,
                        'received_date' => now(),
                    ])),
                // Tombol "Serahkan ke Klien" berada di pintu masuk (CS)
                Tables\Actions\Action::make('return_to_client')
                    ->label('Serahkan ke Klien')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === ReturnRequest::STATUS_READY_FOR_RETURN)
                    ->action(fn($record) => $record->update([
                        'status' => ReturnRequest::STATUS_RETURNED_TO_CLIENT,
                        'returned_to_client_date' => now(),
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Serahkan Unit ke Klien')
                    ->modalDescription('Pastikan unit sudah diserahkan kepada klien. Aksi ini akan menutup tiket RMA.'),
            ]);
    }

    public static function canViewAny(): bool
    {
        // Ganti dengan otorisasi granular (misal: 'customer_service') saat akan rilis.
        return auth()->user()->hasRole(['super_admin', 'inventory_employees']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'create' => Pages\CreateReturnRequest::route('/create'),
            'view' => Pages\ViewReturnRequest::route('/{record}'),
            'edit' => Pages\EditReturnRequest::route('/{record}/edit'),
        ];
    }
}
