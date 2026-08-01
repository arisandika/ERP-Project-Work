<div class="mt-6 mb-2 col-span-full">
    <div
        class="relative flex items-center gap-4 px-6 py-4 border ring-border-light shadow-sm rounded-2xl bg-gradient-to-r from-gray-50 to-white dark:border-gray-700 dark:from-gray-800 dark:to-gray-900">
        {{-- Accent bar kiri --}}
        <div class="absolute top-0 left-0 w-1 h-[80%] rounded-l-2xl bg-gradient-to-b from-primary-500 to-primary-700">
        </div>

        {{-- Icon --}}
        <div
            class="flex items-center justify-center flex-shrink-0 h-11 w-11 rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
            <x-filament::icon :icon="$icon" class="w-6 h-6" />
        </div>

        {{-- Text --}}
        <div class="flex-1">
            <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $title }}
            </h2>
            @if($description)
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </div>

        {{-- Decorative dots --}}
        <div class="items-center hidden gap-1 sm:flex">
            <div class="w-2 h-2 rounded-full bg-primary-300 opacity-60"></div>
            <div class="w-2 h-2 rounded-full bg-primary-400 opacity-80"></div>
            <div class="w-3 h-3 rounded-full bg-primary-500"></div>
        </div>
    </div>
</div>