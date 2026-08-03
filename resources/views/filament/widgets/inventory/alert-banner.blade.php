<x-filament-widgets::widget>
    @if(count($this->getAlerts()) > 0)
        <div class="space-y-2">
            @foreach($this->getAlerts() as $index => $alert)
                <div @class([
                    'flex items-center justify-between p-4 rounded-xl border shadow-sm transition-all',
                    'bg-danger-50 dark:bg-danger-950/30 border-danger-200 dark:border-danger-800' => $alert['level'] === 'danger',
                    'bg-warning-50 dark:bg-warning-950/30 border-warning-200 dark:border-warning-800' => $alert['level'] === 'warning',
                    'bg-info-50 dark:bg-info-950/30 border-info-200 dark:border-info-800' => $alert['level'] === 'info',
                ])>
                    <div class="flex items-center gap-3">
                        <div @class([
                            'flex-shrink-0 p-2 rounded-lg',
                            'bg-danger-100 dark:bg-danger-900/50' => $alert['level'] === 'danger',
                            'bg-warning-100 dark:bg-warning-900/50' => $alert['level'] === 'warning',
                            'bg-info-100 dark:bg-info-900/50' => $alert['level'] === 'info',
                        ])>
                            <x-dynamic-component
                                :component="$alert['icon']"
                                @class([
                                    'w-5 h-5',
                                    'text-danger-600 dark:text-danger-400' => $alert['level'] === 'danger',
                                    'text-warning-600 dark:text-warning-400' => $alert['level'] === 'warning',
                                    'text-info-600 dark:text-info-400' => $alert['level'] === 'info',
                                ])
                            />
                        </div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $alert['message'] }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="$dispatch('filter-stock-alert', { status: '{{ $alert['filter'] ?? '' }}' })"
                            @class([
                                'px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors',
                                'bg-danger-600 hover:bg-danger-700 text-white' => $alert['level'] === 'danger',
                                'bg-warning-600 hover:bg-warning-700 text-white' => $alert['level'] === 'warning',
                                'bg-info-600 hover:bg-info-700 text-white' => $alert['level'] === 'info',
                            ])
                        >
                            {{ $alert['action_label'] }}
                        </button>
                        <button
                            wire:click="dismissAlert({{ $index }})"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <x-heroicon-m-x-mark class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-widgets::widget>
