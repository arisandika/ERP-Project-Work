
<x-filament-panels::page>

    {{-- Project Selector --}}
    @if(!$selectedProject)
        <div class="mb-6">
            <x-filament::section>
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Select Project
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Choose a project to view its board
                    </p>
                </div>

                {{-- Search Bar --}}
                <div class="mb-8">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchProject"
                            placeholder="Search projects by name or prefix..."
                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        />
                        @if($searchProject)
                            <button
                                wire:click="$set('searchProject', '')"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                @if($projects->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                        <h3 class="mb-1 text-base font-medium text-gray-900 dark:text-white">No Projects Available</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">You don't have access to any projects yet.</p>
                    </div>
                @elseif($this->filteredProjects->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="mb-1 text-base font-medium text-gray-900 dark:text-white">No Projects Found</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Try adjusting your search terms</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($this->filteredProjects as $project)
                            <button
                                wire:click="selectProject({{ $project->id }})"
                                class="relative p-4 overflow-hidden text-left transition-all bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 hover:shadow-md"
                                style="border-left: 4px solid {{ $project->color ?? '#6B7280' }};"
                            >
                                {{-- Pin Icon Badge --}}
                                @if($project->is_pinned)
                                    <div class="absolute top-2 right-2">
                                        <div class="flex items-center justify-center w-6 h-6 rounded-full shadow-sm"
                                             style="background-color: {{ $project->color ?? '#6B7280' }};"
                                             title="Pinned Project">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z"/>
                                            </svg>
                                        </div>
                                    </div>
                                @endif

                                {{-- Project Prefix Badge --}}
                                @if($project->ticket_prefix)
                                    @php
                                        $color = $project->color ?? '#6B7280';
                                        // Convert hex to RGB
                                        $hex = ltrim($color, '#');
                                        $r = hexdec(substr($hex, 0, 2));
                                        $g = hexdec(substr($hex, 2, 2));
                                        $b = hexdec(substr($hex, 4, 2));
                                        // Calculate brightness
                                        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                        // Use dark text for light colors, light text for dark colors
                                        $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                                    @endphp
                                    <div class="inline-flex px-2.5 py-1 rounded text-xs font-semibold mb-3"
                                         style="background-color: {{ $color }}; color: {{ $textColor }};">
                                        {{ $project->ticket_prefix }}
                                    </div>
                                @endif

                                {{-- Project Name --}}
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white line-clamp-2">
                                    {{ $project->name }}
                                </h3>
                            </button>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>
    @else
        {{-- Project Switcher --}}
        <div class="mb-4" x-data="{ open: false }">
            <div class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-900 dark:text-white bg-white dark:bg-gray-800 border-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    style="border-color: {{ $selectedProject->color ?? '#D1D5DB' }};"
                >
                    @if($selectedProject->ticket_prefix)
                        @php
                            $color = $selectedProject->color ?? '#6B7280';
                            $hex = ltrim($color, '#');
                            $r = hexdec(substr($hex, 0, 2));
                            $g = hexdec(substr($hex, 2, 2));
                            $b = hexdec(substr($hex, 4, 2));
                            $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                            $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                        @endphp
                        <span class="px-2 py-0.5 rounded text-xs font-semibold"
                              style="background-color: {{ $color }}; color: {{ $textColor }};">
                            {{ $selectedProject->ticket_prefix }}
                        </span>
                    @endif
                    <span>{{ $selectedProject->name }}</span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                {{-- Dropdown Menu --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 z-50 mt-2 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg top-full w-80 dark:bg-gray-800 dark:border-gray-700 max-h-96"
                    style="display: none;"
                >
                    <div class="p-2">
                        <div class="px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            Switch Project
                        </div>
                        @foreach($this->filteredProjects as $project)
                            <button
                                wire:click="selectProject({{ $project->id }})"
                                @click="open = false"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left {{ $project->id === $selectedProject->id ? 'bg-gray-50 dark:bg-gray-700' : '' }}"
                            >
                                @if($project->ticket_prefix)
                                    @php
                                        $color = $project->color ?? '#6B7280';
                                        $hex = ltrim($color, '#');
                                        $r = hexdec(substr($hex, 0, 2));
                                        $g = hexdec(substr($hex, 2, 2));
                                        $b = hexdec(substr($hex, 4, 2));
                                        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                        $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                          style="background-color: {{ $color }}; color: {{ $textColor }};">
                                        {{ $project->ticket_prefix }}
                                    </span>
                                @endif
                                <div class="flex-1 min-w-0 text-sm font-medium text-gray-900 truncate dark:text-white">
                                    {{ $project->name }}
                                </div>
                                @if($project->id === $selectedProject->id)
                                    <svg class="flex-shrink-0 w-4 h-4" style="color: {{ $project->color ?? '#3B82F6' }};" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($selectedProject)
        <div
            wire:key="board-container-{{ $selectedProject->id }}"
            class="no-scrollbar relative overflow-x-auto pb-6 {{ !$this->canMoveTickets() ? 'view-only-mode' : '' }}"
            id="board-container"
        >
            {{-- Mobile swipe hint --}}
            <div class="flex items-center justify-center gap-1 mb-2 text-xs text-gray-500 md:hidden dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Swipe horizontally to view all columns</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </div>

            {{-- View Only Mode Indicator --}}
            @if(!$this->canMoveTickets())
                <div class="flex justify-center mb-4">
                    <div class="inline-flex items-center gap-2 px-4 py-2 border rounded-lg bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span class="text-sm font-medium">View Only Mode</span>
                        <span class="text-xs opacity-75">You can view tickets but cannot move them</span>
                    </div>
                </div>
            @endif

            <div class="inline-flex min-w-full gap-4 pb-2">
                @foreach ($this->ticketStatuses as $status)
                    <div
                        wire:key="status-{{ $status->id }}"
                        class="status-column rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900 w-[calc(85vw-2rem)] min-w-[280px] max-w-[350px] h-[700px] sm:w-[calc((100vw-6rem)/2)] sm:h-[750px] lg:w-[calc((100vw-8rem)/3)] lg:h-[800px] xl:w-[calc((100vw-10rem)/4)] xl:h-[850px]"
                        data-status-id="{{ $status->id }}"
                    >
                        <div
                            class="flex-shrink-0 px-4 py-3 border-b border-gray-200 rounded-t-xl dark:border-gray-700"
                            style="background-color: {{ $status->color ?? '#f3f4f6' }};"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="flex items-center gap-3 font-medium" style="color: white; text-shadow: 0px 0px 1px rgba(0,0,0,0.5);">
                                    <span>{{ $status->name }}</span>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg dark:text-gray-50 dark:bg-gray-700 dark:border-gray-600">{{ $status->tickets->count() }}</span>
                                    @if($status->is_completed)
                                        <div class="flex items-center justify-center w-6 h-6 bg-green-500 border-2 border-white rounded-full shadow-lg" title="Completed Status">
                                            <svg class="w-3 h-3 font-bold text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </h3>

                                <!-- Sort Menu Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button
                                        @click="open = !open"
                                        @click.away="open = false"
                                        class="p-1 transition-colors rounded hover:bg-black hover:bg-opacity-20"
                                        style="color: white;"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>

                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute left-0 z-50 bg-white border border-gray-200 rounded-lg shadow-lg top-8 w-52 dark:bg-gray-800 dark:border-gray-700"
                                        style="display: none; transform: translateX(-100%);"
                                    >
                                        <div class="p-2">
                                            <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">Sort list</span>
                                                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div class="py-1">
                                                <button
                                                    wire:click="setSortOrder({{ $status->id }}, 'date_created_newest')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white"
                                                >
                                                    Date created (newest first)
                                                </button>
                                                <button
                                                    wire:click="setSortOrder({{ $status->id }}, 'date_created_oldest')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white"
                                                >
                                                    Date created (oldest first)
                                                </button>
                                                <button
                                                    wire:click="setSortOrder({{ $status->id }}, 'card_name_alphabetical')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white"
                                                >
                                                    Card name (alphabetically)
                                                </button>
                                                <button
                                                    wire:click="setSortOrder({{ $status->id }}, 'due_date')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white"
                                                >
                                                    Due date
                                                </button>
                                                <button
                                                    wire:click="setSortOrder({{ $status->id }}, 'priority')"
                                                    @click="open = false"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-700 rounded dark:text-white"
                                                >
                                                    Priority
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col flex-1 gap-4 p-3 overflow-y-auto no-scrollbar" style="max-height: calc(100% - 60px);" x-data="{ visibleTickets: 10, totalTickets: {{ $status->tickets->count() }}, scrollPos: 0 }" x-init="$nextTick(() => { $el.addEventListener('scroll', () => { scrollPos = $el.scrollTop; if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100 && visibleTickets < totalTickets) { visibleTickets = Math.min(visibleTickets + 10, totalTickets); } }); })" x-ref="ticketContainer{{ $status->id }}">
                            @foreach ($status->tickets as $index => $ticket)
                                <div
                                    wire:key="ticket-{{ $ticket->id }}-status-{{ $status->id }}"
                                    class="relative p-3 bg-white border border-gray-200 rounded-lg shadow-sm ticket-card dark:bg-gray-800 dark:border-gray-700"
                                    x-show="{{ $index }} < visibleTickets"
                                    style="border-left: 4px solid {{ $ticket->priority->color }};"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                >
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded truncate max-w- sm:max-w-none">
                                            {{ $ticket->uuid }}
                                        </span>
                                        @if ($ticket->due_date)
                                            <span class="inline-flex items-center text-xs whitespace-nowrap {{ $ticket->due_date->isPast() ? 'text-red-800 dark:text-red-300' : 'text-blue-800 dark:text-blue-300' }}">
                                                <x-heroicon-m-calendar class="w-3 h-3 mr-1" />
                                                {{ $ticket->due_date->format('d M y') }}
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="mb-2 text-[15px] font-medium text-gray-900 dark:text-white">{{ $ticket->name }}</h4>

                                    @if ($ticket->description)
                                        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($ticket->description), 100) }}
                                        </p>
                                    @endif

                                    
                                    <div class="flex items-center justify-between gap-3 mt-4">
                                        
                                        @if($this->canMoveTickets())
                                            <div class="relative flex-1 min-w-0" x-data="{ open: false }">
                                                
                                                <div 
                                                    wire:loading 
                                                    wire:target="moveTicket"
                                                    class="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-white/80 dark:bg-gray-800/80 backdrop-blur-[1px]"
                                                >
                                                    <svg class="w-4 h-4 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>

                                                <button
                                                    @click="open = !open"
                                                    @click.away="open = false"
                                                    type="button"
                                                    class="flex items-center justify-between w-full px-2.5 py-1.5 text-xs font-medium text-gray-700 transition-all bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600"
                                                >
                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                        <div class="flex-shrink-0 w-2 h-2 rounded-full shadow-sm" style="background-color: {{ $status->color ?? '#9CA3AF' }};"></div>
                                                        <span class="truncate">{{ $status->name }}</span>
                                                    </div>
                                                    
                                                    <svg class="flex-shrink-0 w-3.5 h-3.5 ml-1 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                    class="absolute left-0 z-30 w-full min-w-[150px] mt-1 overflow-y-auto origin-top bg-white border border-gray-200 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700 max-h-48"
                                                    style="display: none;"
                                                >
                                                    <div class="p-1">
                                                        @foreach($this->ticketStatuses as $statusOption)
                                                            <button
                                                                type="button"
                                                                wire:click="moveTicket({{ $ticket->id }}, {{ $statusOption->id }})"
                                                                @click="open = false"
                                                                class="flex items-center w-full gap-2 px-3 py-2 text-sm text-left rounded-md transition-colors group
                                                                {{ $statusOption->id === $status->id 
                                                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400 font-semibold' 
                                                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700' 
                                                                }}"
                                                            >
                                                                <div class="flex-shrink-0 w-2 h-2 rounded-full" style="background-color: {{ $statusOption->color ?? '#9CA3AF' }};"></div>
                                                                <span class="truncate">{{ $statusOption->name }}</span>
                                                                
                                                                @if($statusOption->id === $status->id)
                                                                    <svg class="w-3.5 h-3.5 ml-auto text-primary-600 dark:text-primary-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <div class="flex items-center flex-shrink-0 gap-2">
                                            
                                            @if ($ticket->assignees->isNotEmpty())
                                                <div class="relative" x-data="{ open: false }">
                                                    <button
                                                        @click="open = !open"
                                                        @click.away="open = false"
                                                        class="flex items-center justify-center w-8 h-8 transition-colors bg-white border border-gray-200 rounded-full shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 text-primary-600 dark:text-primary-500"
                                                        title="View all {{ $ticket->assignees->count() }} assignees"
                                                    >
                                                        <x-heroicon-o-user class="w-4 h-4" />
                                                    </button>

                                                    <div
                                                        x-show="open"
                                                        x-transition:enter="transition ease-out duration-100"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        class="absolute right-0 z-40 w-56 p-2 mt-2 origin-top-right bg-white border border-gray-200 rounded-lg shadow-xl dark:bg-gray-800 dark:border-gray-700"
                                                        style="display: none;"
                                                    >
                                                        <div class="px-2 py-1.5 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase border-b dark:text-gray-400 dark:border-gray-700">
                                                            {{ $ticket->assignees->count() }} Assignees
                                                        </div>
                                                        
                                                        <div class="flex flex-col gap-1 overflow-y-auto max-h-48 custom-scrollbar">
                                                            @foreach($ticket->assignees as $assignee)
                                                                <div class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                                                    <span class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs text-white rounded-full bg-primary-500">
                                                                        {{ substr($assignee->full_name, 0, 1) }}
                                                                    </span>
                                                                    <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-200">
                                                                        {{ $assignee->full_name }}
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>

                                                        @if($ticket->creator) 
                                                            <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>

                                                            <div class="px-2 py-1 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                                                Created By
                                                            </div>

                                                            <div class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                                                <span class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs text-white rounded-full bg-slate-500">
                                                                    {{ substr($ticket->creator->full_name ?? 'Admin', 0, 1) }}
                                                                </span>
                                                                <span class="text-sm font-medium text-gray-700 truncate dark:text-gray-200">
                                                                    {{ $ticket->creator->full_name ?? 'Admin' }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="inline-flex items-center px-2 py-1 text-gray-700 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-gray-400" title="Unassigned">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            @endif

                                            <a
                                                href="{{ \App\Filament\Resources\Project\TicketResource::getUrl('edit', ['record' => $ticket->id]) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                onclick="
                                                    event.preventDefault();
                                                    const container = this.closest('.overflow-y-auto');
                                                    const scrollPos = container.scrollTop;
                                                    window.open(this.href, '_blank');
                                                    setTimeout(() => {
                                                        container.scrollTop = scrollPos;
                                                    }, 0);
                                                    return false;
                                                "
                                                class="flex items-center justify-center w-8 h-8 transition-colors bg-white border border-gray-200 rounded-full shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 text-primary-600 dark:text-primary-500"
                                                title="Edit Ticket"
                                            >
                                                <x-heroicon-o-pencil class="w-4 h-4" />
                                            </a>
                                        </div>

                                    </div>
                                </div>
                            @endforeach

                            @if ($status->tickets->isEmpty())
                                <div class="flex items-center justify-center h-24 text-sm italic text-gray-500 border border-gray-300 border-dashed rounded-lg dark:text-gray-400 dark:border-gray-700">
                                    No tickets
                                </div>
                            @else
                                <!-- Loading indicator for more tickets -->
                                <div x-show="visibleTickets < totalTickets" class="flex items-center justify-center py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Loading more tickets...</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($this->ticketStatuses->isEmpty())
                    <div class="flex items-center justify-center w-full h-40 text-gray-500 dark:text-gray-400">
                        No status columns found for this project
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>