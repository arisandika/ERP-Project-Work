<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseRequisitionResource\Pages;
use App\Models\Procurement\PurchaseRequisition;
use App\Models\Inventory\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PurchaseRequisitionResource extends Resource
{
    protected static ?string $model = PurchaseRequisition::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralModelLabel = 'Purchase Requisitions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Informasi Permintaan')
                        ->schema([
                            Forms\Components\TextInput::make('pr_number')
                                ->label('No. PR')
                                ->default('AUTO-GENERATED')
                                ->disabled(),

                            Forms\Components\DatePicker::make('request_date')
                                ->label('Tanggal Permintaan')
                                ->default(now())
                                ->required(),

                            Forms\Components\DatePicker::make('required_date')
                                ->label('Dibutuhkan Tanggal')
                                ->required(),

                            Forms\Components\Textarea::make('purpose')
                                ->label('Tujuan / Alasan Pembelian')
                                ->required()
                                ->rows(3)
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Daftar Barang (Items)')
                        ->schema([
                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('product_id')
                                        ->label('Barang')
                                        ->options(Product::query()->pluck('product_name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Kuantitas')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),

                                    Forms\Components\TextInput::make('estimated_price')
                                        ->label('Estimasi Harga Satuan (Opsional)')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->default(0),

                                    Forms\Components\TextInput::make('notes')
                                        ->label('Keterangan Spesifik'),
                                ])
                                ->columns(4)
                                ->defaultItems(1)
                                // Matikan fitur tambah/hapus jika sudah diajukan
                                ->addable(fn ($record) => !$record || $record->status === 'draft')
                                ->deletable(fn ($record) => !$record || $record->status === 'draft'),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')->label('No. PR')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('requester.name')->label('Peminta'),
                Tables\Columns\TextColumn::make('request_date')->label('Tgl Minta')->date('d M Y'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'primary',
                    })
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (PurchaseRequisition $record) => $record->status === 'draft'),

                // ACTION: Ajukan Persetujuan
                Tables\Actions\Action::make('submit')
                    ->label('Ajukan Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseRequisition $record) => $record->status === 'draft')
                    ->action(function (PurchaseRequisition $record) {
                        $record->submitForApproval();
                        Notification::make()->title('PR diajukan untuk persetujuan.')->success()->send();
                    }),

                // ACTION: Approve (Hanya untuk Manajer/Superadmin)
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseRequisition $record) => $record->status === 'pending')
                    ->action(function (PurchaseRequisition $record) {
                        $record->approve(auth()->id());
                        Notification::make()->title('PR Disetujui.')->success()->send();
                    }),

                // ACTION: Reject
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseRequisition $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (PurchaseRequisition $record, array $data) {
                        $record->reject(auth()->id(), $data['reason']);
                        Notification::make()->title('PR Ditolak.')->danger()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequisitions::route('/'),
            'create' => Pages\CreatePurchaseRequisition::route('/create'),
            'edit' => Pages\EditPurchaseRequisition::route('/{record}/edit'),
        ];
    }
}
