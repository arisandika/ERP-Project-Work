@if($getRecord())
    @livewire('ticket-comment-form', ['ticket' => $getRecord()])
@else
    <div class="text-sm text-gray-500">
        Simpan ticket terlebih dahulu untuk menambahkan komentar.
    </div>
@endif