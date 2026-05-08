<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\PurchaseRequisitionResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Procurement\PurchaseRequisition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PurchaseRequisitionResource extends Resource
{
    protected static ?string $model = PurchaseRequisition::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Purchase Requisition';
    protected static ?string $pluralModelLabel = 'Purchase Requisitions';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $query = static::getModel()::query();

        if (static::canApproveAny()) {
            $count = $query->where('status', PurchaseRequisition::STATUS_PENDING)->count();
        } else {
            $count = $query
                ->where('requested_by', $user->id)
                ->whereIn('status', [
                    PurchaseRequisition::STATUS_DRAFT,
                    PurchaseRequisition::STATUS_PENDING,
                ])
                ->count();
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    static::requestInformationSection(),
                    static::itemsSection(),
                ])
                ->columnSpanFull(),
        ]);
    }

protected static function requestInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Informasi Permintaan')
            ->description('Lengkapi informasi utama pengajuan pembelian.')
            ->icon('heroicon-o-document-text')
            ->schema([
                Forms\Components\TextInput::make('pr_number_preview')
                    ->label('No. PR')
                    ->disabled()
                    ->dehydrated(false)
                    ->prefixIcon('heroicon-o-hashtag')
                    ->helperText('Nomor PR final akan dibuat otomatis saat data disimpan.')
                    ->afterStateHydrated(function (Forms\Components\TextInput $component, ?PurchaseRequisition $record) {
                        $component->state($record?->pr_number ?? PurchaseRequisition::generatePRNumber());
                    }),

                Forms\Components\DatePicker::make('request_date')
                    ->label('Tanggal Permintaan')
                    ->default(today())
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar-days'),

                Forms\Components\DatePicker::make('required_date')
                    ->label('Tanggal Dibutuhkan')
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-o-calendar')
                    ->minDate(fn (Get $get) => $get('request_date') ?: today())
                    ->rule('after_or_equal:request_date')
                    ->validationMessages([
                        'after_or_equal' => 'Tanggal dibutuhkan harus sama dengan atau setelah tanggal permintaan.',
                    ]),

                Forms\Components\Textarea::make('purpose')
                    ->label('Tujuan / Alasan Pembelian')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Contoh: pembelian laptop untuk tim operasional')
                    ->helperText('Jelaskan alasan pembelian secara singkat dan jelas.'),

                Forms\Components\Placeholder::make('status_preview')
                    ->label('Status')
                    ->content(fn (?PurchaseRequisition $record) => match ($record?->status) {
                        PurchaseRequisition::STATUS_PENDING => 'Pending',
                        PurchaseRequisition::STATUS_APPROVED => 'Approved',
                        PurchaseRequisition::STATUS_REJECTED => 'Rejected',
                        PurchaseRequisition::STATUS_COMPLETED => 'Completed',
                        default => 'Draft',
                    }),
            ])
            ->columns(2);
    }

    protected static function itemsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Daftar Barang')
            ->description('Tambahkan item yang ingin diajukan dalam PR.')
            ->icon('heroicon-o-queue-list')
            ->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->label('Item PR')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->live()
                    ->addActionLabel('Tambah Item')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Barang')
                            ->options(fn () => Product::query()
                                ->orderBy('product_name')
                                ->pluck('product_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->prefixIcon('heroicon-o-cube'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Kuantitas')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->prefixIcon('heroicon-o-calculator'),

                        Forms\Components\TextInput::make('estimated_price')
                            ->label('Estimasi Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->nullable()
                            ->minValue(0)
                            ->placeholder('Opsional')
                            ->prefixIcon('heroicon-o-banknotes'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Keterangan Spesifik')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Contoh: warna hitam, spesifikasi tertentu, merek tertentu'),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->addable(fn (?PurchaseRequisition $record) => static::canModifyDraft($record))
                    ->deletable(fn (?PurchaseRequisition $record) => static::canModifyDraft($record))
                    ->reorderable(fn (?PurchaseRequisition $record) => static::canModifyDraft($record))
                    ->disabled(fn (?PurchaseRequisition $record) => $record ? ! static::canModifyDraft($record) : false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['requester', 'approver', 'items']))
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')
                    ->label('No. PR')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Peminta')
                    ->searchable()
                    ->placeholder('-')
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('request_date')
                    ->label('Tgl Minta')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('required_date')
                    ->label('Tgl Dibutuhkan')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->icon('heroicon-o-queue-list'),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        PurchaseRequisition::STATUS_DRAFT => 'Draft',
                        PurchaseRequisition::STATUS_PENDING => 'Pending',
                        PurchaseRequisition::STATUS_APPROVED => 'Approved',
                        PurchaseRequisition::STATUS_REJECTED => 'Rejected',
                        PurchaseRequisition::STATUS_COMPLETED => 'Completed',
                    ])
                    ->sortable()
                    // 1. Otorisasi UI: Hanya user dengan akses tertentu yang bisa melihat dropdown aktif
                    ->disabled(function (?PurchaseRequisition $record) {
                        $user = Auth::user();
                        if (! $user) return true;

                        // Aturan: Hanya yang bisa approve atau kelola semua yang bisa ganti status seenaknya
                        return ! static::canApproveAny() && ! static::canManageAllDrafts();
                    })
                    // 2. Intersepsi Perubahan State (Business Logic)
                    ->updateStateUsing(function (PurchaseRequisition $record, string $state, string $old) {
                        $user = Auth::user();

                        try {
                            // Mapping transisi state ke method Model untuk menjaga enkapsulasi
                            match ($state) {
                                PurchaseRequisition::STATUS_APPROVED => $record->approve($user->id),

                                // Catatan: Karena via tabel tidak ada modal input, alasan penolakan akan terisi default.
                                PurchaseRequisition::STATUS_REJECTED => $record->reject($user->id, 'Ditolak via ubah status tabel tanpa catatan.'),

                                PurchaseRequisition::STATUS_PENDING => $record->submitForApproval(),

                                default => $record->update(['status' => $state]),
                            };

                            Notification::make()
                                ->title('Status PR berhasil diperbarui.')
                                ->success()
                                ->send();

                            return $state;

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal memperbarui status')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            // Rollback visual di tabel ke status sebelumnya jika terjadi error (seperti validasi gagal)
                            return $old;
                        }
                    }),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->icon('heroicon-o-user-circle')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Tgl Approval')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->icon('heroicon-o-check-badge')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('rejection_note')
                    ->label('Alasan Penolakan')
                    ->limit(40)
                    ->placeholder('-')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PurchaseRequisition::STATUS_DRAFT => 'Draft',
                        PurchaseRequisition::STATUS_PENDING => 'Pending',
                        PurchaseRequisition::STATUS_APPROVED => 'Approved',
                        PurchaseRequisition::STATUS_REJECTED => 'Rejected',
                        PurchaseRequisition::STATUS_COMPLETED => 'Completed',
                    ]),

                Tables\Filters\Filter::make('request_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari')
                            ->prefixIcon('heroicon-o-calendar'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai')
                            ->prefixIcon('heroicon-o-calendar'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (PurchaseRequisition $record): bool => static::canModifyDraft($record)),

                Tables\Actions\Action::make('submit')
                    ->label('Ajukan Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Purchase Requisition')
                    ->modalDescription('Pastikan data dan item sudah benar sebelum diajukan.')
                    ->visible(fn (PurchaseRequisition $record): bool => static::canSubmit($record))
                    ->action(function (PurchaseRequisition $record) {
                        try {
                            $record->submitForApproval();

                            Notification::make()
                                ->title('PR berhasil diajukan untuk persetujuan.')
                                ->success()
                                ->send();

                            return redirect(static::getUrl('index'));
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('PR gagal diajukan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Purchase Requisition')
                    ->visible(fn (PurchaseRequisition $record): bool => static::canApprove($record))
                    ->action(function (PurchaseRequisition $record): void {
                        $record->approve(Auth::id());

                        Notification::make()
                            ->title('PR berhasil disetujui.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseRequisition $record): bool => static::canReject($record))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Masukkan alasan penolakan')
                            ->helperText('Alasan ini akan disimpan sebagai catatan penolakan.'),
                    ])
                    ->action(function (PurchaseRequisition $record, array $data): void {
                        $record->reject(Auth::id(), $data['reason']);

                        Notification::make()
                            ->title('PR berhasil ditolak.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => static::canDeleteAny()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'admin'])
            || $user->can('view_any_procurement::purchase::requisition');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'admin'])
            || $user->can('create_procurement::purchase::requisition');
    }

    public static function canEdit($record): bool
    {
        return static::canModifyDraft($record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $record->isDraft()
            && (static::ownsRecord($record) || static::canManageAllDrafts())
            && (
                $user->hasAnyRole(['Super Admin', 'admin'])
                || $user->can('delete_procurement::purchase::requisition')
            );
    }

    public static function canDeleteAny(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'admin'])
            || $user->can('delete_any_procurement::purchase::requisition');
    }

    protected static function canManageAllDrafts(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'admin'])
            || $user->can('update_any_procurement::purchase::requisition');
    }

    protected static function canApproveAny(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'admin'])
            || $user->can('approve_procurement::purchase::requisition');
    }

    protected static function ownsRecord(PurchaseRequisition $record): bool
    {
        return (int) $record->requested_by === (int) Auth::id();
    }

    protected static function canModifyDraft(?PurchaseRequisition $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (! $record) {
            return $user->hasAnyRole(['Super Admin', 'admin'])
                || $user->can('create_procurement::purchase::requisition');
        }

        if (! $record->isDraft()) {
            return false;
        }

        if (
            ! $user->can('update_procurement::purchase::requisition')
            && ! static::canManageAllDrafts()
            && ! $user->hasAnyRole(['Super Admin', 'admin'])
        ) {
            return false;
        }

        return static::canManageAllDrafts()
            || static::ownsRecord($record)
            || $user->hasAnyRole(['Super Admin', 'admin']);
    }

    protected static function canSubmit(PurchaseRequisition $record): bool
    {
        $user = Auth::user();

        if (! $user || ! $record->isDraft()) {
            return false;
        }

        if (
            ! $user->can('update_procurement::purchase::requisition')
            && ! static::canManageAllDrafts()
            && ! $user->hasAnyRole(['Super Admin', 'admin'])
        ) {
            return false;
        }

        return static::canManageAllDrafts()
            || static::ownsRecord($record)
            || $user->hasAnyRole(['Super Admin', 'admin']);
    }

    protected static function canApprove(PurchaseRequisition $record): bool
    {
        $user = Auth::user();

        if (! $user) return false;

        if (! $record->isPending()) return false;

        if ((int) $record->requested_by === (int) $user->id) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin','super_admin', 'admin'])
            || $user->can('approve_procurement::purchase::requisition');
    }

    protected static function canReject(PurchaseRequisition $record): bool
    {
        return $record->isPending() && static::canApproveAny();
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
