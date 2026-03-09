<div class="p-4 timeline-history">

    <style>
        .timeline-history .vertical-line {
            position: absolute;
            left: 0;
            top: 5px;
            bottom: 5px;
            width: 2px;
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
            left: -5px;
            top: 5px;
        }
    </style>

    <div class="relative">
        <div class="vertical-line bg-border-light dark:bg-border-dark"></div>

        <div class="space-y-5">
            @forelse($histories as $history)
                <div class="timeline-item">
                    <div class="w-3 h-3 rounded-full timeline-dot bg-main-primary ring-2 ring-main-primary/50">
                    </div>

                    <div>
                        <div>
                            <span class="font-semibold"
                                style="color: {{ $history->status->color ?? '#6B7280' }}">{{ $history->status->name }}</span>
                        </div>

                        <div class="flex items-center mt-1 text-xs text-gray-400 gap-x-1">
                            <span>Diperbarui oleh {{ $history->employee->full_name ?? 'System' }}</span>
                            <span class="mx-1 text-gray-300">•</span>
                            <span>{{ $history->created_at->format('d M H:i') }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="pl-6 text-sm text-gray-500">
                    Belum ada riwayat status.
                </div>
            @endforelse
        </div>
    </div>

    @if ($histories->hasPages())
        <div class="custom-pagination">
            {{ $histories->links() }}
        </div>
    @endif

</div>