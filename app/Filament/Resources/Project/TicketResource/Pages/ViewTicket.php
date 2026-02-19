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
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public ?int $editingCommentId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
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
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('uuid')
                                    ->label('Ticket ID')
                                    ->copyable(),

                                TextEntry::make('name')
                                    ->label('Nama Ticket'),

                                TextEntry::make('project.name')
                                    ->label('Nama Project'),

                                TextEntry::make('epic.name')
                                    ->label('Epic')
                                    ->default('No Epic')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-flag'),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Statistik Ticket')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('status.name')
                                    ->label('Status')
                                    ->formatStateUsing(function ($record) {
                                        $color = e($record->status?->color ?? '#6B7280');
                                        $name = e($record->status?->name ?? 'Unknown');

                                        return new HtmlString(<<<HTML
                                        <span class="px-2 py-1 text-xs rounded-md" style="color: #fff; background-color: {$color};">
                                            {$name}
                                        </span>
                                    HTML);
                                    }),

                                TextEntry::make('assignees.full_name')
                                    ->label('Member')
                                    ->badge()
                                    ->separator(',')
                                    ->default('Unassigned')
                                    ->color('info'),

                                TextEntry::make('creator.full_name')
                                    ->label('Dibuat Oleh')
                                    ->default('Unknown'),

                                TextEntry::make('due_date')
                                    ->label('Tanggal Selesai')
                                    ->date('d M Y')
                                    ->color(fn($record) => $record->due_date && $record->due_date->isPast() ? 'danger' : 'success'),
                            ]),
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
                            ->placeholder('No description provided'),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Section::make('Comments')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->description('Discussion about this ticket')
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
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('histories')
                            ->hiddenLabel()
                            ->view('filament.pages.ticket.timeline-history'),
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
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
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
            ->form([
                Hidden::make('comment_id'),
                RichEditor::make('comment')
                    ->label('Edit Comment')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('attachments')
                    ->fileAttachmentsVisibility('public')
                    ->extraInputAttributes(['style' => 'min-height: 10rem;']),
            ])
            ->fillForm(function (array $arguments): array {
                $comment = TicketComment::find($arguments['commentId']);

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
                        ->title('Comment not found')
                        ->danger()
                        ->send();

                    return;
                }

                if ($comment->employee_id !== auth()->user()->employee->id && !auth()->user()->hasRole(['super_admin'])) {
                    Notification::make()
                        ->title('You do not have permission to edit this comment')
                        ->danger()
                        ->send();

                    return;
                }

                $comment->update([
                    'comment' => $data['comment'],
                ]);

                Notification::make()
                    ->title('Comment updated successfully')
                    ->success()
                    ->send();

                $this->dispatch('comment-updated');
            })
            ->modalHeading('Edit Comment')
            ->modalSubmitActionLabel('Update')
            ->modalWidth('2xl');
    }

    public function deleteCommentAction(): Action
    {
        return Action::make('deleteComment')
            ->requiresConfirmation()
            ->modalHeading('Delete Comment')
            ->modalDescription('Are you sure you want to delete this comment? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete it')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->action(function (array $arguments): void {
                $comment = TicketComment::find($arguments['commentId']);

                if (!$comment) {
                    Notification::make()
                        ->title('Comment not found')
                        ->danger()
                        ->send();

                    return;
                }

                if ($comment->employee_id !== auth()->user()->employee->id && !auth()->user()->hasRole(['super_admin'])) {
                    Notification::make()
                        ->title('You do not have permission to delete this comment')
                        ->danger()
                        ->send();

                    return;
                }

                $comment->delete();

                Notification::make()
                    ->title('Comment deleted successfully')
                    ->success()
                    ->send();

                $this->dispatch('comment-deleted');
            });
    }
}
