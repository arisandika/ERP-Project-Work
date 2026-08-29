<div class="mt-6 mb-2 col-span-full-12 w-full">
    <div
        class="w-full relative flex items-center gap-4 px-6 py-4 border border-primary-500 shadow-sm rounded-2xl bg-main-light dark:bg-main-dark">

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