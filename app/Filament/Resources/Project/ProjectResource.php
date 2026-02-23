<?php

namespace App\Filament\Resources\Project;

use App\Filament\Resources\Project\ProjectResource\Pages;
use App\Filament\Resources\Project\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\Project\ProjectResource\RelationManagers;
use App\Filament\Resources\Project\ProjectResource\RelationManagers\EpicsRelationManager;
use App\Filament\Resources\Project\ProjectResource\RelationManagers\MembersRelationManager;
use App\Filament\Resources\Project\ProjectResource\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Project\ProjectResource\RelationManagers\TicketsRelationManager;
use App\Filament\Resources\Project\ProjectResource\RelationManagers\TicketStatusesRelationManager;
use App\Models\Project\Project;
use App\Models\Sales\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'Manajemen Project';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'pm/projects';

    protected static ?string $pluralModelLabel = 'Projects';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Project')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Project')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('description')
                            ->label('Deskripsi Project')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('attachments')
                            ->fileAttachmentsVisibility('public'),

                        Forms\Components\TextInput::make('ticket_prefix')
                            ->label('Prefix Ticket')
                            ->required()
                            ->helperText('Nama prefix untuk Ticket, maksimal 3 karakter. Contoh: BUG, ISS')
                            ->maxLength(3),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Project Color')
                            ->required()
                            ->helperText('Pilih warna untuk card dan badge project'),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\Toggle::make('create_default_statuses')
                            ->label('Status Ticket Default')
                            ->helperText('Buat status Backlog, To Do, In Progress, Review, dan Done secara otomatis')
                            ->default(true)
                            ->dehydrated(false)
                            ->visible(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),

                        Forms\Components\Toggle::make('is_pinned')
                            ->label('Pin Project')
                            ->helperText('Project di-pin akan ditampilkan di bagian atas pada daftar project')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('pinned_date', now());
                                } else {
                                    $set('pinned_date', null);
                                }
                            })
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $get) {
                                $component->state(!is_null($get('pinned_date')));
                            }),

                        Forms\Components\DateTimePicker::make('pinned_date')
                            ->label('Tanggal Pin')
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->visible(fn($get) => $get('is_pinned'))
                            ->dehydrated(true),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Billing & Invoice')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([

                            Forms\Components\Select::make('nx_invoice_id')
                                ->label('Invoice Terkait')
                                ->relationship(
                                    name: 'invoice',
                                    titleAttribute: 'invoice_number',
                                    modifyQueryUsing: fn($query) =>
                                    $query->whereIn('status', ['paid', 'partial'])
                                )
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    self::fillInvoiceDerivedFields($state, $set);
                                })
                                ->afterStateHydrated(function ($state, callable $set) {
                                    self::fillInvoiceDerivedFields($state, $set);
                                }),

                            Forms\Components\TextInput::make('sales_invoice_number')
                                ->label('No. Sales Invoice')
                                ->disabled()
                                ->dehydrated(),

                            Forms\Components\TextInput::make('customer')
                                ->label('Customer')
                                ->disabled()
                                ->reactive()
                                ->prefixIcon('heroicon-o-user-circle'),

                            Forms\Components\Select::make('billing_status')
                                ->label('Status Billing')
                                ->options([
                                    'draft' => 'Draft',
                                    'sent' => 'Terkirim',
                                    'partial' => 'Terbayar Sebagian',
                                    'paid' => 'Lunas',
                                    'cancelled' => 'Dibatalkan',
                                ])
                                ->prefixIcon('heroicon-o-adjustments-vertical')
                                ->disabled()
                                ->reactive(),

                            Forms\Components\DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->disabled()
                                ->reactive()
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->displayFormat('d M Y')
                                ->native(false),

                            Forms\Components\TextInput::make('grand_total')
                                ->label('Nilai Kontrak')
                                ->prefix('IDR')
                                ->disabled()
                                ->reactive(),

                            Forms\Components\TextInput::make('sales_pic')
                                ->label('Sales PIC')
                                ->disabled()
                                ->reactive()
                                ->prefixIcon('heroicon-o-user'),

                        ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('')
                    ->width('40px')
                    ->default('#6B7280'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Project')
                    ->searchable(),

                Tables\Columns\TextColumn::make('ticket_prefix')
                    ->label('Prefix Ticket')
                    ->searchable(),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->getStateUsing(function (Project $record): string {
                        return $record->progress_percentage . '%';
                    })
                    ->badge()
                    ->color(
                        fn(Project $record): string =>
                        $record->progress_percentage >= 100 ? 'success' :
                        ($record->progress_percentage >= 75 ? 'info' :
                            ($record->progress_percentage >= 50 ? 'warning' :
                                ($record->progress_percentage >= 25 ? 'gray' : 'danger')))
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Sisa Hari')
                    ->getStateUsing(function (Project $record): ?string {
                        if (!$record->end_date) {
                            return null;
                        }

                        return $record->remaining_days . ' hari';
                    })
                    ->badge()
                    ->color(
                        fn(Project $record): string =>
                        !$record->end_date ? 'gray' :
                        ($record->remaining_days <= 0 ? 'danger' :
                            ($record->remaining_days <= 7 ? 'warning' : 'success'))
                    ),

                Tables\Columns\ToggleColumn::make('is_pinned')
                    ->label('Pinned')
                    ->updateStateUsing(function ($record, $state) {
                        // Gunakan method pin/unpin yang sudah ada di model
                        if ($state) {
                            $record->pin();
                        } else {
                            $record->unpin();
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members'),

                Tables\Columns\TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->counts('tickets'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            TicketStatusesRelationManager::class,
            MembersRelationManager::class,
            EpicsRelationManager::class,
            TicketsRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    protected static function fillInvoiceDerivedFields(
        ?int $invoiceId,
        callable $set
    ): void {
        if (!$invoiceId) {
            $set('sales_invoice_number', null);
            $set('customer', null);
            $set('billing_status', null);
            $set('due_date', null);
            $set('grand_total', null);
            $set('sales_pic', null);
            return;
        }

        $invoice = Invoice::with(['customer', 'employee'])->find($invoiceId);

        if (!$invoice) {
            return;
        }

        $set('sales_invoice_number', $invoice->invoice_number);
        $set('billing_status', $invoice->status);
        $set('due_date', $invoice->due_date);
        $set('grand_total', $invoice->grand_total);
        $set('sales_pic', $invoice->employee?->full_name);
        $set('customer', $invoice->customer?->name);
    }

}
