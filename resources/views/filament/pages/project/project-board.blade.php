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
            x-data="{
                touchStartX: 0,
                touchStartY: 0,
                scrollStartX: 0,

                init() {
                    this.$nextTick(() => {
                        this.setupTouchScrolling();
                    });
                },

                setupTouchScrolling() {
                    const container = document.getElementById('board-container');

                    container.addEventListener('touchstart', (e) => {
                        this.touchStartX = e.touches[0].clientX;
                        this.touchStartY = e.touches[0].clientY;
                        this.scrollStartX = container.scrollLeft;
                    }, { passive: true });

                    container.addEventListener('touchmove', (e) => {
                        if (e.touches.length !== 1) return;

                        const touchX = e.touches[0].clientX;
                        const touchY = e.touches[0].clientY;
                        const moveX = this.touchStartX - touchX;
                        const moveY = this.touchStartY - touchY;

                        if (Math.abs(moveX) > Math.abs(moveY)) {
                            e.preventDefault();
                            container.scrollLeft = this.scrollStartX + moveX;
                        }
                    }, { passive: false });
                }
            }"
            x-init="init()"
            wire:ignore.self
            wire:key="board-container-{{ $selectedProject->id }}"
            class="relative overflow-x-auto pb-6 {{ !$this->canMoveTickets() ? 'view-only-mode' : '' }}"
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
                        wire:key="status-column-{{ $status->id }}"
                        class="status-column rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900 w-[calc(85vw-2rem)] min-w-[280px] max-w-[350px] h-[700px] sm:w-[calc((100vw-6rem)/2)] sm:h-[750px] lg:w-[calc((100vw-8rem)/3)] lg:h-[800px] xl:w-[calc((100vw-10rem)/4)] xl:h-[850px]"
                        data-status-id="{{ $status->id }}"
                    >
                        <div
                            class="flex-shrink-0 px-4 py-3 border-b border-gray-200 rounded-t-xl dark:border-gray-700"
                            style="background-color: {{ $status->color ?? '#f3f4f6' }};"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="flex items-center gap-2 font-medium" style="color: white; text-shadow: 0px 0px 1px rgba(0,0,0,0.5);">
                                    <span>{{ $status->name }}</span>
                                    <span class="text-sm opacity-80">{{ $status->tickets->count() }}</span>
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

                        <div wire:ignore.self class="flex flex-col flex-1 gap-3 p-3 overflow-y-auto" style="max-height: calc(100% - 60px);" x-data="{ visibleTickets: 10, totalTickets: {{ $status->tickets->count() }}, scrollPos: 0 }" x-init="$nextTick(() => { $el.addEventListener('scroll', () => { scrollPos = $el.scrollTop; if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 100 && visibleTickets < totalTickets) { visibleTickets = Math.min(visibleTickets + 10, totalTickets); } }); })" x-ref="ticketContainer{{ $status->id }}">
                            @foreach ($status->tickets as $index => $ticket)
                                <div
                                    wire:key="ticket-{{ $status->id }}-{{ $ticket->id }}"
                                    class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm cursor-move ticket-card dark:bg-gray-800 dark:border-gray-700"
                                    data-ticket-id="{{ $ticket->id }}"
                                    x-show="{{ $index }} < visibleTickets"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded truncate max-w-[120px] sm:max-w-none">
                                            {{ $ticket->uuid }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            @if ($ticket->priority)
                                                <span class="text-xs px-1.5 py-0.5 rounded whitespace-nowrap text-white font-medium" style="background-color: {{ $ticket->priority->color }};">
                                                    {{ $ticket->priority->name }}
                                                </span>
                                            @endif
                                            @if ($ticket->due_date)
                                                <span class="text-xs px-1.5 py-0.5 rounded whitespace-nowrap {{ $ticket->due_date->isPast() ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                                    {{ $ticket->due_date->format('M d') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <h4 class="mb-2 font-medium text-gray-900 dark:text-white">{{ $ticket->name }}</h4>

                                    @if ($ticket->description)
                                        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($ticket->description), 100) }}
                                        </p>
                                    @endif

                                    <div class="flex items-center justify-between mt-2">
                                       @if ($ticket->assignees->isNotEmpty())
                                            <div class="flex flex-wrap gap-1 max-w-[180px]">
                                                @foreach($ticket->assignees as $assignee)
                                                    <div class="inline-flex items-center gap-1 py-1 pl-1 pr-2 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300">
                                                        <span class="flex items-center justify-center flex-shrink-0 w-4 h-4 text-xs text-white rounded-full bg-primary-500">
                                                            {{ substr($assignee->full_name, 0, 1) }}
                                                        </span>
                                                        <span class="text-xs font-medium truncate">{{ \Illuminate\Support\Str::limit($assignee->full_name, 8) }}</span>
                                                    </div>
                                                @endforeach
                                                @if($ticket->assignees->count() > 2)
                                                    <!-- <div class="inline-flex items-center px-2 py-1 text-gray-700 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-gray-400">
                                                        <span class="text-xs font-medium">+{{ $ticket->assignees->count() - 2 }}</span>
                                                    </div> -->
                                                @endif
                                            </div>
                                        @else
                                            <div class="inline-flex items-center px-2 py-1 text-gray-700 bg-gray-100 rounded-full dark:bg-gray-800 dark:text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 w-4 h-4 mr-1 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-xs font-medium">Unassigned</span>
                                            </div>
                                        @endif

                                        <a
                                            href="{{ \App\Filament\Resources\Project\TicketResource::getUrl('view', ['record' => $ticket->id]) }}"
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
                                            class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-sm font-medium border border-gray-200 rounded-lg dark:border-gray-700 text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400"
                                        >
                                            <x-heroicon-m-eye class="w-4 h-4" />
                                        </a>
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

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/base/jquery-ui.min.css" />
<style>
    .ui-sortable-helper {
        opacity: 0.7;
        transform: rotate(2deg);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        z-index: 9999 !important;
    }
    .ui-sortable-placeholder {
        visibility: visible !important;
        border: 2px dashed rgba(var(--primary-500), 0.5) !important;
        background: rgba(var(--primary-50), 0.3) !important;
        border-radius: 0.5rem;
        min-height: 60px;
        margin-bottom: 0.75rem;
    }
    .sortable-drop-active {
        background-color: rgba(var(--primary-50), 0.5) !important;
    }
    .dark .sortable-drop-active {
        background-color: rgba(var(--primary-950), 0.3) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
<script>
    (function() {
        var sortableReady = false;

        function initSortable() {
            var $containers = $('.status-column .overflow-y-auto');
            if (!$containers.length) return;

            // Skip if view-only mode
            var board = document.getElementById('board-container');
            if (board && board.classList.contains('view-only-mode')) return;

            // Skip if already initialized
            if (sortableReady && $containers.first().sortable('instance')) return;

            $containers.sortable({
                connectWith: '.status-column .overflow-y-auto',
                items: '> .ticket-card',
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                revert: 100,
                scroll: true,
                scrollSensitivity: 50,
                scrollSpeed: 20,
                forcePlaceholderSize: true,
                zIndex: 9999,

                start: function(event, ui) {
                    ui.item.addClass('opacity-50');
                    ui.placeholder.height(ui.item.outerHeight());
                },

                over: function(event, ui) {
                    $(this).closest('.status-column').addClass('sortable-drop-active');
                },

                out: function(event, ui) {
                    $(this).closest('.status-column').removeClass('sortable-drop-active');
                },

                stop: function(event, ui) {
                    ui.item.removeClass('opacity-50');
                    $('.status-column').removeClass('sortable-drop-active');
                },

                update: function(event, ui) {
                    // Only fire on the receiving container (not the sender)
                    if (this !== ui.item.parent()[0]) return;

                    var ticketId = ui.item.data('ticket-id');
                    var newStatusId = $(this).closest('.status-column').data('status-id');

                    if (ticketId && newStatusId) {
                        // Find the closest Livewire component from the ticket (ProjectBoard)
                        var wireEl = ui.item.closest('[wire\\:id]');
                        if (wireEl.length) {
                            var componentId = wireEl.attr('wire:id');
                            Livewire.find(componentId).call('moveTicket', parseInt(ticketId), parseInt(newStatusId));
                        }
                    }
                }
            });

            sortableReady = true;
        }

        // Initialize once when Livewire is ready
        document.addEventListener('livewire:init', function() {
            setTimeout(initSortable, 500);
        });

        // Reinitialize after Livewire re-renders (correct Livewire 3 event)
        document.addEventListener('livewire:rendered', function() {
            sortableReady = false;
            setTimeout(initSortable, 300);
        });
    })();
</script>
@endpush
