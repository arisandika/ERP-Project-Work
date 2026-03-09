<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class TicketHistoryTimeline extends Component
{
    use WithPagination; 

    public $ticket;

    public function render()
    {
        $histories = $this->ticket->histories()
            ->with(['employee', 'status'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.ticket-history-timeline',[
            'histories' => $histories
        ]);
    }
}
