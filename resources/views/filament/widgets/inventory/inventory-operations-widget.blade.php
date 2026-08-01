<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($this->getData() as $key => $data)
            <div class="flex flex-col p-6 bg-white dark:bg-gray-800 rounded-xl border ring-border-light dark:border-gray-700 shadow-sm transition hover:shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 rounded-lg {{ $data['bg'] }}">
                        <x-dynamic-component :component="$data['icon']" class="w-6 h-6 {{ $data['color'] }}" />
                    </div>
                    <span class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $data['count'] }}
                    </span>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ $data['label'] }}</h3>
                    <p class="text-xs text-gray-400 mt-1 italic">Butuh diproses</p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="#" class="text-xs font-bold text-primary-600 hover:text-primary-500 flex items-center">
                        LIHAT DETAIL
                        <x-heroicon-m-chevron-right class="w-3 h-3 ml-1" />
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
