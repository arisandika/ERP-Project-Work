<?php

namespace App\Filament\Resources\Project\TicketResource\Pages;

use App\Filament\Pages\Project\ProjectBoard;
use App\Filament\Resources\Project\TicketResource;
use App\Models\Project\Ticket;
use App\Models\Project\TicketComment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public ?int $editingCommentId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('Kembali')
                ->label('Back to Board')
                ->color('gray')
                ->url(fn() => ProjectBoard::getUrl(['project_id' => $this->record->project_id])),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Ticket';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Ticket')
                    ->description('Detail project, epic, status dan informasi ticket.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Ticket')
                            ->weight('semibold')
                            ->size('lg')
                            ->placeholder('—'),

                        TextEntry::make('uuid')
                            ->label('Ticket ID')
                            ->weight('semibold')
                            ->placeholder('—')
                            ->icon('heroicon-o-hashtag')
                            ->copyable(),

                        TextEntry::make('project.name')
                            ->label('Nama Project'),

                        TextEntry::make('epic.name')
                            ->label('Nama Epic')
                            ->placeholder('—'),

                        TextEntry::make('status.name')
                            ->label('Status Pengerjaan')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'To Do' => 'warning',
                                'In Progress' => 'info',
                                'Review' => 'primary',
                                'Done' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                        TextEntry::make('priority.name')
                            ->label('Prioritas Ticket')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'High' => 'danger',
                                'Medium' => 'warning',
                                'Low' => 'success',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Jadwal Pengerjaan')
                    ->description('Timeline target penyelesaian ticket.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('d M Y'),

                        TextEntry::make('due_date')
                            ->label('Tanggal Selesai')
                            ->date('d M Y')
                            ->color(fn($record) => $record->due_date < now() && $record->status?->name !== 'Done' ? 'danger' : 'gray'),

                        TextEntry::make('remaining_days')
                            ->label('Sisa Hari')
                            ->getStateUsing(function ($record) {
                                if (!$record->due_date || $record->status?->name === 'Done')
                                    return 'Selesai';
                                $days = now()->diffInDays($record->due_date, false);
                                return $days < 0 ? abs((int) $days) . ' Hari terlambat' : (int) $days . ' Hari';
                            })
                            ->color(fn($state) => str_contains($state, 'terlambat') ? 'danger' : 'success'),
                    ]),

                Section::make('Member Ticket')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('assignees.full_name')
                            ->label('Ditugaskan Kepada')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-o-user')
                            ->placeholder('Belum ada member ditugaskan')
                            ->listWithLineBreaks(),

                        TextEntry::make('creator.full_name')
                            ->label('Dibuat Oleh')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),
                    ]),

                Section::make('Deskripsi Ticket')
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->getStateUsing(function (Ticket $record) {
                                return $this->convertVideoImgsToVideoTags($record->description);
                            })
                            ->columnSpanFull()
                            ->placeholder('Tidak ada deskripsi'),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('Komentar')
                    ->description('Diskusi tentang ticket ini')
                    ->schema([
                        TextEntry::make('comments_list')
                            ->hiddenLabel()
                            ->state(function (Ticket $record) {
                                if (method_exists($record, 'comments')) {
                                    return $record->comments()->with('employee')->oldest()->get();
                                }

                                return collect();
                            })
                            ->view('filament.pages.ticket.comments-section')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('Riwayat Status')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('histories')
                            ->hiddenLabel()
                            ->view('infolists.components.ticket-history'),
                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),
                    ]),
            ]);
    }

    protected function convertVideoImgsToVideoTags($html)
    {
        // Pattern to match img tags with video file extensions
        $pattern = '/<img\s+[^>]*src=["\']([^"\']*\.(mp4|webm|mov|avi|mkv))["\'][^>]*\/?>/i';

        return preg_replace_callback($pattern, function ($matches) {
            $videoUrl = $matches[1];
            return '<video controls class="max-w-full my-2 rounded-lg" style="max-height: 400px;">
                    <source src="' . $videoUrl . '" type="video/' . pathinfo($videoUrl, PATHINFO_EXTENSION) . '">
                    Your browser does not support the video tag.
                </video>';
        }, $html);
    }

    public function editCommentAction(): Action
    {
        return Action::make('editComment')
            ->label('Ubah Komentar')
            ->modalHeading('Ubah Komentar')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->modalWidth('2xl')
            ->form([
                Hidden::make('comment_id'),

                RichEditor::make('comment')
                    ->label('Komentar')
                    ->required()
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
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
                    ->fileAttachmentsVisibility('public')
                    ->extraInputAttributes(['style' => 'min-height: 10rem;']),
            ])
            ->fillForm(function (array $arguments): array {
                // Ambil ID dari arguments yang dikirim via blade mountAction
                $commentId = $arguments['commentId'] ?? null;
                $comment = TicketComment::find($commentId);

                if (!$comment) {
                    return [];
                }

                return [
                    'comment_id' => $comment->id,
                    'comment' => $comment->comment,
                ];
            })
            ->action(function (array $data): void {
                $comment = TicketComment::find($data['comment_id']);

                if (!$comment) {
                    Notification::make()
                        ->title('Komentar tidak ditemukan')
                        ->danger()
                        ->send();
                    return;
                }

                // Cek Permission: Hanya Pemilik atau Super Admin
                $currentEmployeeId = auth()->user()->employee?->id;
                $isSuperAdmin = auth()->user()->hasRole(['super_admin']);

                if ($comment->employee_id !== $currentEmployeeId && !$isSuperAdmin) {
                    Notification::make()
                        ->title('Akses Ditolak')
                        ->body('Anda tidak memiliki izin untuk mengubah komentar ini.')
                        ->danger()
                        ->send();
                    return;
                }

                $comment->update([
                    'comment' => $data['comment'],
                ]);

                Notification::make()
                    ->title('Komentar berhasil diperbarui')
                    ->success()
                    ->send();

                // Refresh komponen livewire
                $this->dispatch('comment-updated');
            });
    }

    public function deleteCommentAction(): Action
    {
        return Action::make('deleteComment')
            ->requiresConfirmation()
            ->modalHeading('Hapus Komentar')
            ->modalDescription('Apakah Anda yakin ingin menghapus komentar ini? Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->modalCancelActionLabel('Batal')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->action(function (array $arguments): void {
                $commentId = $arguments['commentId'] ?? null;
                $comment = TicketComment::find($commentId);

                if (!$comment) {
                    Notification::make()
                        ->title('Komentar tidak ditemukan')
                        ->danger()
                        ->send();
                    return;
                }

                // Cek Permission: Hanya Pemilik atau Super Admin
                $currentEmployeeId = auth()->user()->employee?->id;
                $isSuperAdmin = auth()->user()->hasRole(['super_admin']);

                if ($comment->employee_id !== $currentEmployeeId && !$isSuperAdmin) {
                    Notification::make()
                        ->title('Akses Ditolak')
                        ->body('Anda tidak memiliki izin untuk menghapus komentar ini.')
                        ->danger()
                        ->send();
                    return;
                }

                $comment->delete();

                Notification::make()
                    ->title('Komentar berhasil dihapus')
                    ->success()
                    ->send();

                // Refresh komponen livewire
                $this->dispatch('comment-deleted');
            });
    }
}
