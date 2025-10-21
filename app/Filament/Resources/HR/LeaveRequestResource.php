<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\LeaveRequestResource\Pages;
use App\Models\HR\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Manajemen Presensi';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = '/leave-requests';

    protected static ?string $pluralModelLabel = 'Pengajuan Cuti';

    public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Section::make('Data Pengajuan Cuti')
            ->description('Isi data berikut untuk mengajukan cuti.')
            ->schema([
                Forms\Components\Select::make('leave_id')
                    ->label('Jenis Cuti')
                    ->relationship('leave', 'leave_type')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->prefixIcon('heroicon-o-briefcase'),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->closeOnDateSelection()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $get) {
                        $start_date = $get('start_date');
                        $end_date = $get('end_date');

                        if ($start_date && $end_date) {
                            $startDate = Carbon::parse($start_date);
                            $endDate = Carbon::parse($end_date);

                            $workingDays = $startDate->diffInDaysFiltered(fn(Carbon $date) => !$date->isWeekend(), $endDate);
                            $set('total_days', $workingDays + 1);
                        } else {
                            $set('total_days', null);
                        }
                    }),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->closeOnDateSelection()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state, $get) {
                        $start_date = $get('start_date');
                        $end_date = $state;

                        if ($start_date && $end_date) {
                            $startDate = Carbon::parse($start_date);
                            $endDate = Carbon::parse($end_date);

                            $workingDays = $startDate->diffInDaysFiltered(fn(Carbon $date) => !$date->isWeekend(), $endDate);
                            $set('total_days', $workingDays + 1);
                        } else {
                            $set('total_days', null);
                        }
                    }),

                Forms\Components\TextInput::make('total_days')
                    ->label('Total Cuti (Hari Kerja)')
                    ->prefixIcon('heroicon-o-clock')
                    ->disabled()
                    ->dehydrated(false)
                    ->reactive(),

                Forms\Components\Textarea::make('reason')
                    ->label('Alasan Cuti')
                    ->placeholder('Tuliskan alasan pengajuan cuti...')
                    ->rows(3)
                    ->maxLength(500),
            ])
            ->columns(2),

        // Bagian ini hanya muncul untuk admin/super_admin
        Forms\Components\Section::make('Status Pengajuan')
            ->visible(fn() => auth()->user()->hasRole('super_admin'))
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('Status Pengajuan')
                    ->required()
                    ->options([
                        'pending' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('pending')
                    ->prefixIcon('heroicon-o-clipboard-document-check'),
            ]),

        Forms\Components\Section::make('Persetujuan Atasan / HRD')
            ->visible(fn() => auth()->user()->hasRole('super_admin'))
            ->description('Bagian ini hanya diisi oleh HR atau Atasan setelah meninjau pengajuan.')
            ->schema([
                Forms\Components\Select::make('approved_by')
                    ->label('Disetujui Oleh')
                    ->required()
                    ->relationship('approver', 'full_name')
                    ->searchable()
                    ->preload()
                    ->prefixIcon('heroicon-o-user'),

                Forms\Components\Textarea::make('approval_note')
                    ->label('Catatan Persetujuan')
                    ->rows(2)
                    ->maxLength(300)
                    ->placeholder('Tambahkan catatan jika perlu...'),
            ]),
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

                Tables\Columns\TextColumn::make('leave.leave_type')
                    ->label('Jenis Cuti')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Durasi (Hari)')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pending'  => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default    => ucwords($state),
                    }),

                Tables\Columns\TextColumn::make('approver.full_name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->label('Status'),

                Tables\Filters\Filter::make('tanggal_pengajuan')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(
                        fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('end_date', '<=', $date))
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->displayFormat('d/m/Y')
                            ->native(false),
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
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index'  => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view'   => Pages\ViewLeaveRequest::route('/{record}'),
            'edit'   => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $user = auth()->user();

        // Jika bukan super_admin, hanya tampilkan data cutinya sendiri
        if (! $user->hasRole('super_admin')) {
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
