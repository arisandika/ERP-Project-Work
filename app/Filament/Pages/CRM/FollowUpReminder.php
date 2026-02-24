<?php

namespace App\Filament\Pages\CRM;

use App\Enums\CRM\DealStatus;
use App\Models\CRM\Deal;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;

class FollowUpReminder extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'Manajemen CRM';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Follow Up & Reminder';
    protected static ?string $slug = 'crm/follow-up';
    protected static string $view = 'filament.pages.crm.follow-up-reminder';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn() => Deal::query()
                    ->with('customer')
                    ->whereNotIn('status', [
                        DealStatus::Deal->value,
                        DealStatus::Closed->value,
                    ])
                    ->orderBy('next_follow_up_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Penawaran')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('next_follow_up_at')
                    ->label('Jadwal Follow Up')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->color(fn($record) => $record->next_follow_up_at?->isPast() ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(DealStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('kirim_email')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->form([
                        TextInput::make('subject')
                            ->label('Subjek Email')
                            ->required()
                            ->default(fn($record) => 'Follow Up: ' . $record->title),

                        RichEditor::make('body')
                            ->label('Isi Email')
                            ->required()
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'link']),
                    ])
                    ->action(function (Deal $record, array $data) {
                        Mail::html($data['body'], function ($message) use ($record, $data) {
                            $message->to($record->customer->email, $record->customer->name)
                                ->subject($data['subject']);
                        });

                        Notification::make()
                            ->title('Email berhasil dikirim ke ' . $record->customer->name)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('lihat_pipeline')
                    ->label('Lihat di Pipeline')
                    ->icon('heroicon-o-funnel')
                    ->url(fn() => DealPipeline::getUrl())
                    ->openUrlInNewTab(),
            ]);
    }
}
