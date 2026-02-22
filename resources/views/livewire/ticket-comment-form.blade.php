<div>
    
    <form wire:submit="addComment">
        {{ $this->form }}

        <div class="flex justify-end mt-3" style="margin-top: 20px">
            <x-filament::button type="submit" size="sm" wire:loading.attr="disabled" wire:target="addComment">
                <span wire:loading wire:target="addComment">
                    Posting...
                </span>
                <span wire:loading.remove wire:target="addComment">
                    Post Comment
                </span>
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />

</div>