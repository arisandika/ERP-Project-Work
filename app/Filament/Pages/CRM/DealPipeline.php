<?php

namespace App\Filament\Pages\CRM;

use App\Enums\CRM\DealStatus;
use App\Models\CRM\Deal;
use App\Filament\Resources\Sales\QuotationResource; // Pastikan namespace ini sesuai dengan project lu
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;

class DealPipeline extends KanbanBoard
{
    protected static string $model = Deal::class;
    protected static string $statusEnum = DealStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationGroup = 'Manajemen CRM';
    protected static ?string $navigationLabel = 'Deal Pipeline';
    protected static ?string $title = 'Deal Pipeline';
    protected static ?string $slug = 'crm/deal-pipeline';

    protected static string $recordTitleAttribute = 'title';

    protected function statuses(): Collection
    {
        return collect(DealStatus::cases())->map(fn ($status) => [
            'id'    => $status->value,
            'title' => $status->getLabel(),
        ]);
    }

    protected function getEditModalFormSchema(null|int|string $recordId): array
    {
        return [
            // Tambahkan Tombol Action di atas form
            Actions::make([
                Action::make('convert_to_quotation')
                    ->label('Convert to Quotation')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    // Arahkan ke halaman Create Quotation bawa parameter
                    ->url(fn ($record) => QuotationResource::getUrl('create', [
                        'nx_deal_id'     => $record?->id,
                        'nx_customer_id' => $record?->customer_id,
                    ]))
                    // Buka di tab baru biar kanban gak hilang
                    ->openUrlInNewTab()
                    // Sembunyikan jika lagi mode Create (record belum ada) atau sudah punya quotation
                    ->hidden(fn ($record) => !$record || $record->quotation()->exists()),
            ]),

            Select::make('customer_id')
                ->label('Pelanggan')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('title')
                ->label('Judul Penawaran')
                ->required(),

            TextInput::make('value')
                ->label('Nilai Penawaran')
                ->numeric()
                ->prefix('Rp'),

            Textarea::make('notes')
                ->label('Catatan Nego')
                ->rows(3),

            DateTimePicker::make('next_follow_up_at')
                ->label('Jadwal Follow Up Selanjutnya')
                ->native(false),

            FileUpload::make('attachments')
                ->label('Dokumen Terkait (Proposal, NDA, dll)')
                ->directory('crm-deals')
                ->multiple()
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->maxSize(5120),

            Select::make('lost_reason')
                ->label('Alasan Gagal')
                ->options([
                    'price'      => 'Harga Terlalu Tinggi',
                    'competitor' => 'Kalah oleh Kompetitor',
                    'budget'     => 'Budget Klien Tidak Cukup',
                    'ghosting'   => 'Klien Hilang / Tidak Merespon',
                    'other'      => 'Lainnya',
                ])

            ->visible(fn (Get $get) => $get('status') === DealStatus::Closed->value)
            ->required(fn (Get $get) => $get('status') === DealStatus::Closed->value),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->model(Deal::class)
                ->label('New Deal')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = DealStatus::Proposal->value;
                    return $data;
                })
                ->form($this->getEditModalFormSchema(null)),
        ];
    }
}
