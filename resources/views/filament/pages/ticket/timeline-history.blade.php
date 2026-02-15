{{-- resources/views/filament/resources/ticket-resource/timeline-history.blade.php --}}

<div class="timeline-history">
    <style>
        .timeline-history .vertical-line {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 2px;
            background-color: #94a3b8;
        }
        
        .timeline-history .timeline-item {
            position: relative;
            padding-left: 25px;
            padding-bottom: 1.25rem;
        }
        
        .timeline-history .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-history .timeline-dot {
            position: absolute;
            left: -9px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #34d399;
        }
    </style>

    @php
        $histories = $getRecord()->histories()->with(['employee', 'status'])->orderBy('created_at', 'desc')->get();
    @endphp
    
    <div class="relative">
        {{-- Vertical line --}}
        <div class="vertical-line"></div>
        
        {{-- Timeline items --}}
        <div class="space-y-5">
            @foreach($histories as $history)
                <div class="timeline-item">
                    {{-- Dot marker --}}
                    <div class="timeline-dot"></div>
                    
                    {{-- Content --}}
                    <div>
                        <div>
                            <span class="text-base font-medium text-gray-900 dark:text-white">{{ $history->status->name }}</span>
                        </div>
                        
                        <div class="flex items-center mt-1 text-xs text-gray-400 gap-x-1">
                            <span>Updated by: {{ $history->user->name ?? 'System' }}</span>
                            <span class="mx-1 text-gray-300">•</span>
                            <span>{{ $history->created_at->format('d M H:i') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>