<?php

namespace App\Livewire;

use App\Models\Project\Ticket;
use Filament\Forms\Form;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;

class TicketCommentForm extends Component implements HasForms, HasActions
{
    use InteractsWithForms;

    use InteractsWithActions;

    public Ticket $ticket;

    public $newComment = '';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                RichEditor::make('newComment')
                    ->label('Tambah komentar')
                    ->required()
                    ->placeholder('Tuliskan komentar disini...')
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
                    ->fileAttachmentsVisibility('public')
                    ->extraInputAttributes(['style' => 'min-height: 10rem;']),
            ]);
    }

    public function addComment()
    {
        $data = $this->form->getState();

        $this->ticket->comments()->create([
            'employee_id' => auth()->user()->employee->id,
            'comment' => $data['newComment']
        ]);

        auth()->user()->notifications()
            ->where('data->ticket_id', $this->ticket->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Notification::make()
            ->title('Komentar berhasil dikirim')
            ->success()
            ->send();

        $this->form->fill();

        $this->dispatch('comment-added');
    }

    public function render()
    {
        return view('livewire.ticket-comment-form');
    }
}
