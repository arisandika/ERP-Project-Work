<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\LeaveRequestResource\Pages;
use App\Models\HR\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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

    protected static ?int $navigationSort = 2;

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
                        ->relationship(
                            'leave',
                            'leave_type',
                            modifyQueryUsing: function ($query) {
                                $employee = auth()->user()?->employee;

                                // Jika bukan perempuan, sembunyikan cuti khusus wanita
                                if ($employee && $employee->gender !== 'Perempuan') {
                                    $query->where('is_female_only', false);
                                }

                                return $query;
                            }
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-o-briefcase'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->displayFormat('d M Y')
                        ->native(false)
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->closeOnDateSelection()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $get) {
                            $start = $get('start_date');
                            $end = $get('end_date');

                            if ($start && $end) {
                                $startDate = Carbon::parse($start);
                                $endDate = Carbon::parse($end);

                                // Swap jika admin salah input (biar ga error)
                                if ($startDate->gt($endDate)) {
                                    $set('total_days', null);
                                    return;
                                }

                                $workingDays = $startDate->diffInDaysFiltered(
                                    fn(Carbon $date) => !$date->isWeekend(),
                                    $endDate
                                );

                                // INKLUSI hari mulai + hari selesai
                                $set('total_days', $workingDays + 1);
                            } else {
                                $set('total_days', null);
                            }
                        }),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->displayFormat('d M Y')
                        ->native(false)
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->closeOnDateSelection()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state, $get) {
                            $start = $get('start_date');
                            $end = $state;

                            if ($start && $end) {
                                $startDate = Carbon::parse($start);
                                $endDate = Carbon::parse($end);

                                if ($startDate->gt($endDate)) {
                                    $set('total_days', null);
                                    return;
                                }

                                $workingDays = $startDate->diffInDaysFiltered(
                                    fn(Carbon $date) => !$date->isWeekend(),
                                    $endDate
                                );

                                $set('total_days', $workingDays + 1);
                            } else {
                                $set('total_days', null);
                            }
                        }),

                    Forms\Components\TextInput::make('total_days')
                        ->label('Durasi Cuti (Hari Kerja)')
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

                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(LeaveRequest $record) => $record->status === 'pending'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Pengajuan Cuti')
                    ->description('Kamu bisa edit pengajuan cuti ini jika masih berstatus pending atau menunggu persetujuan.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nama Karyawan'),

                        TextEntry::make('leave.leave_type')
                            ->label('Jenis Cuti'),

                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('d M Y'),

                        TextEntry::make('end_date')
                            ->label('Tanggal Selesai')
                            ->date('d M Y'),

                        TextEntry::make('total_days')
                            ->label('Durasi (Hari Kerja)')
                            ->numeric(),

                        TextEntry::make('reason')
                            ->label('Alasan Cuti')
                            ->columnSpanFull()
                            ->placeholder('-'),
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

                        TextEntry::make('approval_note')
                            ->label('Catatan Admin')
                            ->placeholder('-')
                            ->columnSpanFull(),
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
                            ->visible(fn($record) => $record->trashed()),
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
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
