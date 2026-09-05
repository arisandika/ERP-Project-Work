<?php

namespace App\Filament\Resources\AfterSales;

use App\Filament\Resources\AfterSales\SupplierWarrantyResource\Pages;
use App\Models\AfterSales\ReturnRequest;
use App\Services\AfterSales\ReturnWorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\BelongsToModule;

class SupplierWarrantyResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'inventory';
    protected static ?string $model = ReturnRequest::class;
    protected static ?string $modelLabel = 'Klaim Vendor';
    protected static ?string $pluralModelLabel = 'Daftar Klaim Vendor';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen After-Sales';
    protected static ?string $navigationLabel = 'Klaim Vendor';
    protected static ?string $slug = 'after-sales/supplier-warranty';
    protected static ?int $navigationSort = 23;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('warranty_type', 'supplier')
            ->whereIn('status', [ReturnRequest::STATUS_RECEIVED, ReturnRequest::STATUS_SENT_TO_VENDOR]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rma_number')->label('No. RMA')->weight('bold'),
                Tables\Columns\TextColumn::make('serialNumber.serial_number')->label('SN Unit'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('send_to_vendor')
                    ->label('Kirim ke Vendor')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === ReturnRequest::STATUS_RECEIVED)
                    ->form([
                        Forms\Components\DatePicker::make('sent_to_vendor_date')->default(now())->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => ReturnRequest::STATUS_SENT_TO_VENDOR,
                        'sent_to_vendor_date' => $data['sent_to_vendor_date'],
                    ])),

                Tables\Actions\Action::make('receive_from_vendor')
                    ->label('Terima dari Vendor')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === ReturnRequest::STATUS_SENT_TO_VENDOR)
                    ->form([
                        Forms\Components\Radio::make('resolution_type')
                            ->options(['repaired' => 'Repair (SN Tetap)', 'replaced' => 'Replace (SN Baru)'])
                            ->inline()->live()->required(),
                        Forms\Components\TextInput::make('new_serial_number')
                            ->visible(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                            ->required(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                            ->unique('nx_serial_number', 'serial_number'),
                        Forms\Components\Textarea::make('vendor_notes')->required(),
                    ])
                    ->action(function (ReturnRequest $record, array $data) {
                        $service = app(ReturnWorkflowService::class);
                        $service->receiveFromVendor($record, $data);
                    }),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'inventory_employees']);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierWarranties::route('/'),
        ];
    }
}
