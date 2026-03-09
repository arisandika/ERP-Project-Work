<x-filament-panels::page>
    
    {{-- Project Selector --}}
    @php
        $selectedProject = $selectedProjectId ? $availableProjects->firstWhere('id', $selectedProjectId) : null;
    @endphp

    @if(!$selectedProject)
        <div class="mb-6">
            <x-filament::section>
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-black dark:text-white">
                        Pilih Project
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Pilih project untuk melihat daftar epic
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
                            placeholder="Cari project berdasarkan nama atau prefix..."
                            class="block w-full pl-10 pr-3 py-2.5 border border-border-light dark:border-border-dark rounded-full bg-main-light dark:bg-accent-dark text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-main-primary focus:border-transparent outline-none"
                        />
                        @if($searchProject)
                            <button
                                wire:click="$set('searchProject', '')"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-black dark:hover:text-white"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                @if($availableProjects->isEmpty())
                    <div class="flex flex-col items-center justify-center h-64 text-center text-gray-500">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mb-2 text-lg font-medium text-black dark:text-white">Tidak ada project yang tersedia</h3>
                        <p class="text-sm">Kamu belum memiliki akses ke project mana pun</p>
                    </div>
                @elseif($this->filteredProjects->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <h3 class="mb-1 text-base font-medium text-black dark:text-white">Project tidak ditemukan</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Coba ubah kata kunci pencarian</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($this->filteredProjects as $project)
                            <button
                                wire:click="$set('selectedProjectId', {{ $project->id }})"
                                class="relative p-4 overflow-hidden text-left transition-all border rounded-2xl bg-main-light border-border-light dark:bg-accent-dark dark:border-border-dark hover:shadow-md hover:bg-main-light dark:hover:bg-main-dark"
                                style="border-left: 4px solid {{ $project->color ?? '#6B7280' }};"
                            >
                                {{-- Pin Icon Badge --}}
                                @if($project->is_pinned)
                                    <div class="absolute top-2 right-2">
                                        <div class="flex items-center justify-center w-6 h-6 rounded-full shadow-sm"
                                             style="background-color: {{ $project->color ?? '#6B7280' }};"
                                             title="Project Disematkan">
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
                                        $hex = ltrim($color, '#');
                                        $r = hexdec(substr($hex, 0, 2));
                                        $g = hexdec(substr($hex, 2, 2));
                                        $b = hexdec(substr($hex, 4, 2));
                                        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                        $textColor = $brightness > 155 ? '#1F2937' : '#FFFFFF';
                                    @endphp
                                    <div class="inline-flex px-2.5 py-1 rounded text-xs font-semibold mb-3"
                                         style="background-color: {{ $color }}; color: {{ $textColor }};">
                                        {{ $project->ticket_prefix }}
                                    </div>
                                @endif

                                {{-- Project Name --}}
                                <h3 class="text-base font-semibold text-black dark:text-white line-clamp-2">
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
        <div class="flex items-center justify-between gap-3" x-data="{ open: false }">
            <div class="relative">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium text-black transition-colors border-2 dark:text-white bg-secondary-light dark:bg-accent-dark rounded-2xl hover:bg-main-light dark:hover:bg-main-dark"
                    style="border-color: {{ $selectedProject->color ?? '#d9dbdc' }};"
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
                    <span class="text-left">{{ $selectedProject->name }}</span>
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
                    class="absolute left-0 z-50 mt-2 overflow-y-auto border shadow-lg rounded-2xl bg-secondary-light border-border-light top-full w-80 dark:bg-secondary-dark dark:border-border-dark max-h-96"
                    style="display: none;"
                >
                    <div class="p-2">
                        <div class="p-3 text-sm font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                            Ganti Project
                        </div>
                        @foreach($this->filteredProjects as $project)
                            <button
                                wire:click="$set('selectedProjectId', {{ $project->id }})"
                                @click="open = false"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-main-light dark:hover:bg-main-dark transition-colors text-left {{ $project->id === $selectedProjectId ? 'bg-main-light dark:bg-main-dark' : '' }}"
                            >
                                @if($project->is_pinned)
                                    <div class="flex items-center justify-center w-5 h-5 rounded-full shrink-0"
                                         style="background-color: {{ $project->color ?? '#6B7280' }};"
                                         title="Disematkan">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 9V4h1c.55 0 1-.45 1-1s-.45-1-1-1H7c-.55 0-1 .45-1 1s.45 1 1 1h1v5c0 1.66-1.34 3-3 3v2h5.97v7l1 1 1-1v-7H19v-2c-1.66 0-3-1.34-3-3z"/>
                                        </svg>
                                    </div>
                                @endif
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
                                <div class="flex-1 min-w-0 text-sm font-medium text-black truncate dark:text-white">
                                    {{ $project->name }}
                                </div>
                                @if($project->id === $selectedProjectId)
                                    <svg class="flex-shrink-0 w-4 h-4" style="color: {{ $project->color ?? '#1c9cf0' }};" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <a href="{{ url()->previous() }}" class="items-center px-4 py-3 text-sm font-medium text-black transition-colors rounded-full dark:text-white bg-main-light hover:bg-secondary-light dark:bg-main-dark ring-1 ring-border-light dark:ring-border-dark dark:hover:bg-secondary-dark">
                Kembali
            </a>
        </div>
    @endif

    @if($selectedProjectId && $epics->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">
                Ringkasan Epic
            </x-slot>

            <div class="w-full space-y-3">
                @foreach($epics as $epic)
                    <div class="overflow-hidden border rounded-2xl bg-secondary-light border-border-light dark:bg-secondary-dark dark:border-border-dark">
                        <div
                            class="flex flex-col items-center justify-between gap-2 px-4 py-3 border-b cursor-pointer border-border-light md:flex-row bg-accent-light dark:bg-accent-dark dark:border-border-dark"
                            wire:click="toggleEpic({{ $epic->id }})"
                        >
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-black dark:text-white">{{ $epic->name }}</h3>
                                    <div class="hidden text-sm text-gray-500 dark:text-gray-400 md:block">
                                        {{ $epic->start_date ? $epic->start_date->format('M d, Y') : '-' }} -
                                        {{ $epic->end_date ? $epic->end_date->format('M d, Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="px-3 py-1 text-sm text-black rounded-full bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark dark:text-gray-400">
                                    {{ $epic->tickets->count() }} Ticket
                                </div>
                                <button class="text-gray-400 transition-colors hover:text-main-primary focus:outline-none">
                                    @if($this->isExpanded($epic->id))
                                        <x-heroicon-s-chevron-down class="w-5 h-5 text-main-primary" />
                                    @else
                                        <x-heroicon-s-chevron-right class="w-5 h-5 dark:text-gray-400" />
                                    @endif
                                </button>
                            </div>
                        </div>

                        <!-- Epic Content - Accordion Content -->
                        @if($this->isExpanded($epic->id))
                            <div class="p-4">
                                <!-- Epic Description -->
                                @if($epic->description)
                                    <div class="mb-4">
                                        <h4 class="mb-2 text-sm font-semibold text-black dark:text-white">Deskripsi Epic</h4>
                                        <div class="py-4 text-sm text-black rounded-2xl bg-secondary-light dark:bg-secondary-dark dark:text-gray-400">
                                            {!! $epic->description !!}
                                        </div>
                                    </div>
                                @endif

                                <!-- Tickets -->
                                <div class="w-full">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-semibold text-black dark:text-white">Daftar Ticket</h4>
                                        <a href="{{ route('filament.admin.resources.pm.tickets.create',['epic_id' => $epic->id]) }}" class="text-sm font-semibold text-main-primary hover:text-main-primary/80">
                                            <x-heroicon-s-plus class="inline-block w-4 h-4 mr-1" />
                                            Tambah Ticket
                                        </a>
                                    </div>

                                    @if($epic->tickets->isEmpty())
                                        <div class="w-full p-4 text-sm text-center text-gray-500 border border-dashed rounded-2xl border-border-light dark:text-gray-400 bg-main-light dark:bg-main-dark dark:border-border-dark">
                                            Belum ada ticket untuk epic ini
                                        </div>
                                    @else
                                        <div class="w-full overflow-x-auto border rounded-2xl border-border-light dark:border-border-dark">
                                            <table class="w-full divide-y divide-border-light dark:divide-border-dark">
                                                <thead class="bg-accent-light dark:bg-accent-dark">
                                                    <tr>
                                                        <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">ID</th>
                                                        <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">Ticket</th>
                                                        <th scope="col" class="p-4 text-sm font-semibold text-left text-black dark:text-white">Status</th>
                                                        <th scope="col" class="hidden p-4 text-sm font-semibold text-left text-black dark:text-white sm:table-cell">Ditugaskan</th>
                                                        <th scope="col" class="hidden p-4 text-sm font-semibold text-left text-black dark:text-white md:table-cell">Tanggal Selesai</th>
                                                        <th scope="col" class="relative px-3 py-2">
                                                            <span class="sr-only">Actions</span>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y bg-main-light divide-border-light dark:bg-main-dark dark:divide-border-dark">
                                                    @foreach($epic->tickets as $ticket)
                                                        <tr class="transition-colors hover:bg-secondary-light dark:hover:bg-secondary-dark">
                                                            <td class="p-4 text-sm font-medium text-black whitespace-nowrap dark:text-white">
                                                                {{ $ticket->uuid }}
                                                            </td>
                                                            <td class="p-4 text-sm text-black dark:text-white">
                                                                {{ $ticket->name }}
                                                            </td>
                                                            <td class="p-4 text-sm whitespace-nowrap">
                                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                                                    {{ match($ticket->status->name ?? '') {
                                                                        'To Do' => 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400',
                                                                        'In Progress' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                                                        'Review' => 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
                                                                        'Done' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                                                        default => 'bg-secondary-light dark:bg-secondary-dark ring-1 ring-border-light dark:ring-border-dark text-black dark:text-white',
                                                                    } }}">
                                                                    {{ $ticket->status->name ?? 'No Status' }}
                                                                </span>
                                                            </td>
                                                            <td class="hidden p-3 text-sm text-gray-500 dark:text-gray-400 sm:table-cell">
                                                                @if($ticket->assignees->isEmpty())
                                                                    <x-filament::badge color="gray" icon="heroicon-m-user-minus">
                                                                        Belum Ditugaskan
                                                                    </x-filament::badge>
                                                                @else
                                                                    <div class="flex flex-wrap gap-1">
                                                                        @foreach($ticket->assignees->take(2) as $assignee)
                                                                            <x-filament::badge
                                                                                color="primary"
                                                                                icon="heroicon-o-user"
                                                                                size="sm"
                                                                            >
                                                                                {{ $assignee->name }}
                                                                            </x-filament::badge>
                                                                        @endforeach

                                                                        @if($ticket->assignees->count() > 2)
                                                                            <x-filament::badge
                                                                                color="gray"
                                                                                size="sm"
                                                                                :tooltip="$ticket->assignees->skip(2)->pluck('name')->implode(', ')"
                                                                            >
                                                                                +{{ $ticket->assignees->count() - 2 }}
                                                                            </x-filament::badge>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td class="hidden p-3 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400 md:table-cell">
                                                                {{ $ticket->due_date ? $ticket->due_date->format('d M Y') : '-' }}
                                                            </td>
                                                            <td class="p-4 text-sm font-medium text-right whitespace-nowrap">
                                                                <a href="{{ route('filament.admin.resources.pm.tickets.view',['record' => $ticket->id]) }}" target="_blank" class="text-sm transition-colors text-main-primary hover:text-main-primary/80">
                                                                    View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @elseif($selectedProjectId && $epics->isEmpty())
        {{-- No Epics Found State --}}
        <div class="flex flex-col items-center justify-center h-64 gap-4 text-gray-500 dark:text-gray-400">
            <div class="flex items-center justify-center p-6 rounded-full bg-secondary-light ring-1 ring-border-light dark:bg-secondary-dark dark:ring-border-dark">
                <x-heroicon-o-flag class="w-16 h-16 text-gray-400 dark:text-gray-500" />
            </div>
            <h2 class="text-xl font-medium text-gray-600 dark:text-gray-400">Belum ada epic di project ini</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Project ini belum memiliki epic. Buat epic untuk mengelola ticket-nya
            </p>
        </div>
    @endif

</x-filament-panels::page>