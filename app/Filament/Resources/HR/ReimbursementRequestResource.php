<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\ReimbursementRequestResource\Pages;
use App\Filament\Resources\HR\ReimbursementRequestResource\RelationManagers;
use App\Models\HR\ReimbursementRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReimbursementRequestResource extends Resource
{
    protected static ?string $model = ReimbursementRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Manajemen Presensi';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = '/reimburse-requests';

    protected static ?string $pluralModelLabel = 'Pengajuan Reimburse';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pengajuan Reimburse')
                ->description('Isi data berikut untuk mengajukan reimburse.')
                ->schema([

                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal Transaksi')
                        ->required()
                        ->default(now())
                        ->displayFormat('d M Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->prefixIcon('heroicon-o-calendar-days'),

                    Forms\Components\Select::make('type')
                        ->label('Jenis Reimburse')
                        ->required()
                        ->options([
                            'Bensin' => 'Bensin',
                            'Makan' => 'Makan',
                            'Transport' => 'Transport',
                            'Parkir' => 'Parkir',
                            'Hotel' => 'Hotel',
                            'Lainnya' => 'Lainnya (Tulis di keterangan)',
                            // 'Kirim Barang' => 'Kirim Barang',
                            // 'Akomodasi' => 'Akomodasi',
                            // 'Peralatan Kantor' => 'Peralatan Kantor',
                            // 'Kesehatan' => 'Kesehatan',
                            // 'Pelatihan' => 'Pelatihan',
                            // 'Internet' => 'Internet',
                            // 'Pulsa/Telepon' => 'Pulsa/Telepon',
                            // 'Representasi' => 'Representasi',
                            // 'Perbaikan Kendaraan' => 'Perbaikan Kendaraan',
                            // 'Lainnya' => 'Lainnya (Tulis di keterangan)',
                        ])
                        ->searchable()
                        ->prefixIcon('heroicon-o-tag'),

                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal')
                        ->numeric()
                        ->required()
                        ->prefix('IDR')
                        ->minValue(0)
                        ->step(1000)
                        ->placeholder('Contoh: 50000')
                        ->prefixIcon('heroicon-o-banknotes'),

                    Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->placeholder('Tuliskan keterangan reimburse...')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('receipt')
                        ->label('Upload Bukti')
                        ->required()
                        ->image()
                        ->directory('reimbursements')
                        ->imageEditor()
                        ->maxSize(2048)
                        ->helperText('Upload foto struk maksimal 2MB')
                        ->columnSpanFull(),

                ])
                ->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucwords($state),
                    }),

                Tables\Columns\TextColumn::make('approver.full_name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->label('Status'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Section::make('Informasi Pengajuan Reimburse')
                    ->description('Kamu bisa edit pengajuan reimburse ini jika masih berstatus pending atau menunggu persetujuan.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan'),

                        TextEntry::make('date')
                            ->label('Tanggal Transaksi')
                            ->date('d M Y'),

                        TextEntry::make('type')
                            ->label('Jenis Reimburse')
                            ->badge(),

                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR'),

                        TextEntry::make('description')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->placeholder('-'),

                        ImageEntry::make('receipt')
                            ->label('Bukti Transaksi')
                            ->columnSpanFull()
                            ->width('500px')
                            ->height('auto'),

                    ]),

                Section::make('Status Persetujuan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary',
                            })
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                        TextEntry::make('approver.full_name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),

                        TextEntry::make('approved_at')
                            ->label('Waktu Persetujuan')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->approved_at !== null),

                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([

                        TextEntry::make('created_at')
                            ->label('Diajukan Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(
                                fn($record) =>
                                method_exists($record, 'trashed')
                                && $record->trashed()
                            ),

                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReimbursementRequests::route('/'),
            'create' => Pages\CreateReimbursementRequest::route('/create'),
            'view' => Pages\ViewReimbursementRequest::route('/{record}'),
            'edit' => Pages\EditReimbursementRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            //
        ]);

        $user = auth()->user();

        // Jika bukan super_admin, hanya tampilkan data cutinya sendiri
        if (!$user->hasRole('super_admin')) {
            $employee = $user->employee;
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                // Jika user belum punya relasi employee, kosongkan query
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }
}
