<div class="min-h-screen transition-colors duration-300 bg-main-light dark:bg-main-dark">

    <!-- Flash Messages (Tetap Absolute/Fixed) -->
    @if (session('message'))
    <div class="fixed z-50 px-4 py-3 text-blue-600 bg-blue-100 rounded-md shadow-2xl top-20 right-4"
        id="success-message">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-sm font-medium">{{ session('message') }}</span>
        </div>
    </div>
    @endif

    @if (session('error'))
    <div class="fixed z-50 px-4 py-3 text-red-600 bg-red-100 rounded-md shadow-2xl top-20 right-4" id="error-message">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                </path>
            </svg>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    <!-- 1. NEW TOP NAVBAR -->
    <nav
        class="sticky top-0 z-40 w-full border-b backdrop-blur-md bg-secondary-light/80 dark:bg-secondary-dark/90 border-border-light dark:border-border-dark">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left: Branding / Project Name -->
                <div>
                    <a href="https://erp.arihub.my.id/dashboard">
                        <div style="height: 1.5rem;" class="flex fi-logo">
                            <div class="flex items-center">
                                <img src="{{ asset('assets/logo-dark.png') }}" alt="Logo" class="h-8 dark:hidden">
                                <img src="{{ asset('assets/logo-light.png') }}" alt="Logo"
                                    class="hidden h-8 dark:block">
                            </div>
                        </div>
                    </a>
                    <p class="text-[10px] font-medium mt-2 tracking-wide text-gray-500 uppercase dark:text-gray-400">
                        External Dashboard</p>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Refresh Button (Icon Only on Mobile, Text on Desktop) -->
                    <button wire:click="refreshData" wire:loading.attr="disabled"
                        class="flex items-center justify-center p-2 text-sm font-medium text-gray-600 transition-colors bg-transparent rounded-lg hover:bg-main-light dark:text-gray-300 dark:hover:bg-main-dark dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-border-light dark:focus:ring-border-dark">
                        <svg wire:loading.remove class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        <svg wire:loading class="w-5 h-5 animate-spin" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                    </button>

                    <!-- Theme Toggle -->
                    <button @click="toggleTheme()"
                        class="p-2 text-gray-600 transition-colors duration-200 rounded-full hover:bg-main-light dark:text-gray-400 dark:hover:bg-main-dark focus:outline-none focus:ring-2 focus:ring-border-light dark:focus:ring-border-dark">
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                            </path>
                        </svg>
                    </button>

                    <div class="h-6 mx-1 border-l border-border-light dark:border-border-dark"></div>

                    <!-- Logout Button -->
                    <button wire:click="logout"
                        class="flex items-center px-3 py-2 space-x-1 text-sm font-medium text-white transition-colors duration-200 bg-red-600 rounded-lg shadow-sm hover:bg-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- 2. MAIN LAYOUT (Split View) -->
    <div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

            <!-- LEFT COLUMN: STATS & OVERVIEW (Span 4) -->
            <div class="space-y-6 lg:col-span-4 lg:sticky lg:top-24 h-fit">

                <h2 class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">Overview
                    Project</h2>

                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-bold leading-tight text-black dark:text-white">{{ $project->name }}</h1>
                    </div>
                </div>

                <!-- 1. STATS GRID (Sama seperti sebelumnya) -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-1">

                    <!-- Card: Progress -->
                    <div
                        class="p-5 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Progress</p>
                            <span
                                class="p-2 text-green-600 bg-green-200 rounded-lg dark:bg-green-900/20 dark:text-green-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="flex items-end gap-2">
                            <span class="text-3xl font-bold tracking-tight text-black dark:text-white">{{
                                $projectStats['progress_percentage'] ?? 0 }}%</span>
                        </div>
                        <div class="w-full h-1.5 mt-3 bg-gray-200 rounded-full dark:bg-gray-700 overflow-hidden">
                            <div class="h-full transition-all duration-500 rounded-full bg-gradient-to-r from-green-400 to-green-600"
                                style="width: {{ min($projectStats['progress_percentage'] ?? 0, 100) }}%"></div>
                        </div>
                    </div>

                    <!-- Compact Stats -->
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="p-4 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                            <div class="p-2 mb-3 bg-yellow-200 rounded-xl w-fit dark:bg-yellow-900/20">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-2xl font-bold text-black dark:text-white">{{ $projectStats['remaining_days']
                                ?? '-' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sisa Hari</p>
                        </div>
                        <div
                            class="p-4 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                            <div class="p-2 mb-3 bg-purple-200 rounded-xl w-fit dark:bg-purple-900/20">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                            </div>
                            <p class="text-2xl font-bold text-black dark:text-white">{{ $projectStats['total_tickets']
                                ?? 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Tugas</p>
                        </div>
                    </div>
                </div>

                <!-- 2. CLIENT / CUSTOMER INFO CARD (NEW) -->
                @if($project->salesOrder && $project->salesOrder->customer)
                <div
                    class="overflow-hidden transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                    <div
                        class="px-5 py-4 border-b border-border-light dark:border-border-dark bg-gray-50/50 dark:bg-white/5">
                        <h3 class="text-sm font-semibold text-black dark:text-white">Informasi Client</h3>
                    </div>
                    <div class="p-5 space-y-4">

                        <!-- Company Info -->
                        <div class="flex items-start gap-3">
                            <div
                                class="flex items-center justify-center w-8 h-8 mt-1 text-blue-600 bg-blue-100 rounded-lg dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-black dark:text-white">{{
                                    $project->salesOrder->customer->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{
                                    $project->salesOrder->customer->address ?? 'Alamat tidak tersedia' }}</p>
                            </div>
                        </div>

                        <hr class="border-dashed border-border-light dark:border-border-dark">

                        <!-- PIC Info -->
                        <div class="space-y-3">
                            <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">Person In Charge
                                (PIC)</p>

                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center w-8 h-8 text-gray-500 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-black dark:text-white">{{
                                        $project->salesOrder->customer->pic_name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{
                                        $project->salesOrder->customer->pic_position ?? 'Perwakilan Client' }}</p>
                                </div>
                            </div>

                            <!-- Contact Actions -->
                            @if($project->salesOrder->customer->pic_phone || $project->salesOrder->customer->email)
                            <div class="grid grid-cols-2 gap-2 mt-2">
                                @if($project->salesOrder->customer->pic_phone)
                                <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $project->salesOrder->customer->pic_phone)) }}"
                                    target="_blank"
                                    class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-green-700 transition-colors bg-green-100 rounded-lg hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                    WhatsApp
                                </a>
                                @endif

                                @if($project->salesOrder->customer->email)
                                <a href="mailto:{{ $project->salesOrder->customer->email }}"
                                    class="flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Email
                                </a>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- 3. INTERNAL TEAM & ACCOUNT MANAGER (NEW) -->
                <div
                    class="overflow-hidden transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                    <div
                        class="px-5 py-4 border-b border-border-light dark:border-border-dark bg-gray-50/50 dark:bg-white/5">
                        <h3 class="text-sm font-semibold text-black dark:text-white">Internal Team</h3>
                    </div>

                    <div class="p-5 space-y-5">

                        <!-- Account Manager (Sales) -->
                        @if($project->salesOrder && $project->salesOrder->employee)
                        <div class="flex items-center gap-3">
                            @if($project->salesOrder->employee->photo)
                            <img src="{{ asset('storage/' . $project->salesOrder->employee->photo) }}"
                                class="object-cover w-10 h-10 rounded-full ring-2 ring-white dark:ring-gray-800"
                                alt="AM">
                            @else
                            <div
                                class="flex items-center justify-center w-10 h-10 text-xs font-bold text-white rounded-full bg-main-primary ring-2 ring-white dark:ring-gray-800">
                                {{ substr($project->salesOrder->employee->full_name, 0, 2) }}
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-black truncate dark:text-white">{{
                                    $project->salesOrder->employee->full_name }}</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">Account Manager</p>
                            </div>
                            <!-- Call AM Button -->
                            @if($project->salesOrder->employee->phone_number)
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $project->salesOrder->employee->phone_number)) }}"
                                target="_blank" class="p-2 text-gray-400 transition-colors hover:text-green-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                            </a>
                            @endif
                        </div>
                        @endif

                        <!-- Project Members (Squard) -->
                        @if($project->members->count() > 0)
                        <div>
                            <p class="mb-3 text-xs font-semibold tracking-wide text-gray-400 uppercase">Project Squad
                            </p>
                            <div class="flex -space-x-2 overflow-hidden">
                                @foreach($project->members->take(5) as $member)
                                @if($member->photo)
                                <img class="inline-block object-cover w-8 h-8 rounded-full ring-2 ring-white dark:ring-secondary-dark"
                                    src="{{ asset('storage/' . $member->photo) }}" alt="{{ $member->full_name }}"
                                    title="{{ $member->full_name }}">
                                @else
                                <div class="inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-white bg-gray-400 rounded-full ring-2 ring-white dark:ring-secondary-dark"
                                    title="{{ $member->full_name }}">
                                    {{ substr($member->full_name, 0, 1) }}
                                </div>
                                @endif
                                @endforeach
                                @if($project->members->count() > 5)
                                <div
                                    class="inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-white bg-gray-800 rounded-full ring-2 ring-white dark:ring-secondary-dark">
                                    +{{ $project->members->count() - 5 }}
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: WORKSPACE TABS & CONTENT (Span 8) -->
            <div class="lg:col-span-8">

                <!-- Tab Layout Container (Preserved ID for JS) -->
                <div class="transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark"
                    id="dashboard-tabs">

                    <!-- Custom Clean Tab Navigation -->
                    <div class="px-6 pt-6 pb-0 border-b border-border-light dark:border-border-dark">
                        <!-- Desktop/Tablet Tab Navigation -->
                        <nav x-data="{ 
                            activeTab: sessionStorage.getItem('currentActiveTab') || 'tasks',
                            setActive(tab) {
                                this.activeTab = tab;
                                switchTab(tab); 
                            }
                        }" class="flex space-x-6" aria-label="Tabs">

                            <button type="button" @click="setActive('tasks')"
                                :class="activeTab === 'tasks' 
                                ? 'border-main-primary text-main-primary' 
                                : 'border-transparent text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="pb-4 text-sm font-medium transition-all duration-200 border-b-2">
                                Project Tasks
                            </button>

                            <button type="button" @click="setActive('activity')"
                                :class="activeTab === 'activity' 
                                ? 'border-main-primary text-main-primary' 
                                : 'border-transparent text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                class="pb-4 text-sm font-medium transition-all duration-200 border-b-2">
                                Recent Activity
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content Wrapper -->
                    <div class="min-h-[500px]"> <!-- Tambahkan min-height agar tidak jumping -->

                        <!-- Tasks Tab -->
                        <div id="tasks-tab" class="tab-content">
                            <!-- Filters Area -->
                            <div class="p-6 pb-4">
                                <div class="flex flex-col gap-3 lg:flex-row">
                                    <!-- Search -->
                                    <div class="flex-1">
                                        <div
                                            class="fi-input-wrp py-2 flex rounded-xl shadow-sm ring-1 transition duration-75 bg-main-light dark:bg-main-dark/50 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500">
                                            <div class="flex items-center gap-x-3 ps-3 pe-2">
                                                <svg wire:loading.remove.delay.default="1" wire:target="search"
                                                    class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                                <svg wire:loading.delay.default="" wire:target="search"
                                                    class="w-5 h-5 text-gray-400 animate-spin" fill="none"
                                                    viewBox="0 0 24 24">
                                                    <path clip-rule="evenodd"
                                                        d="M12 19C15.866 19 19 15.866 19 12C19 8.13401 15.866 5 12 5C8.13401 5 5 8.13401 5 12C5 15.866 8.13401 19 12 19ZM12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                                                        fill-rule="evenodd" fill="currentColor" opacity="0.2"></path>
                                                    <path
                                                        d="M2 12C2 6.47715 6.47715 2 12 2V5C8.13401 5 5 8.13401 5 12H2Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <input type="text" wire:model.live.debounce.300ms="searchTerm"
                                                    placeholder="Cari tugas..."
                                                    class="block w-full text-sm text-black bg-transparent border-none outline-none focus:ring-0 dark:text-white placeholder:text-gray-400">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Filter & Clear -->
                                    <div class="flex gap-3">
                                        <select wire:model.live="selectedStatus"
                                            class="px-4 py-2 text-sm text-black bg-main-light ring-1 ring-border-light rounded-xl dark:text-white dark:bg-main-dark/50 dark:ring-border-dark focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none min-w-[140px]">
                                            <option value="">Semua Status</option>
                                            @foreach ($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                            @endforeach
                                        </select>

                                        @if ($selectedStatus || $searchTerm)
                                        <button wire:click="clearFilters"
                                            onclick="setTimeout(() => switchTab(getCurrentTab()), 150)"
                                            class="px-4 py-2 text-sm font-medium text-red-600 transition-colors bg-red-50 rounded-xl hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 ring-1 ring-red-100 dark:ring-red-900/30">
                                            Reset
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Table Area -->
                            <div class="overflow-hidden border-t border-border-light dark:border-border-dark">
                                <!-- Desktop Table -->
                                <table
                                    class="hidden min-w-full divide-y divide-border-light sm:table dark:divide-border-dark">
                                    <thead class="bg-secondary-light dark:bg-secondary-dark">
                                        <tr>
                                            <th
                                                class="px-6 py-4 text-sm font-semibold text-left text-black dark:text-white">
                                                Kode</th>
                                            <th
                                                class="px-6 py-4 text-sm font-semibold text-left text-black dark:text-white">
                                                Nama Tugas</th>
                                            <th
                                                class="px-6 py-4 text-sm font-semibold text-left text-black dark:text-white">
                                                Tenggat</th>
                                            <th
                                                class="px-6 py-4 text-sm font-semibold text-left text-black dark:text-white">
                                                Status</th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-border-light dark:divide-border-dark bg-main-light dark:bg-main-dark">
                                        @forelse($this->tickets as $ticket)
                                        <tr
                                            class="transition-colors hover:bg-secondary-light dark:hover:bg-secondary-dark">
                                            <td
                                                class="px-6 py-4 text-sm font-semibold text-black whitespace-nowrap dark:text-white">
                                                {{ $ticket->uuid }}</td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-black dark:text-white">
                                                    {{ $ticket->name }}</div>
                                                @if ($ticket->description)
                                                <div class="max-w-md mt-1 text-xs text-black truncate dark:text-white">
                                                    {{ strip_tags(Str::limit($ticket->description, 100)) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($ticket->due_date)
                                                <span
                                                    class="text-sm {{ \Carbon\Carbon::parse($ticket->due_date)->isPast() ? 'text-red-600 dark:text-red-400 font-medium' : 'text-black dark:text-gray-300' }}">
                                                    {{ \Carbon\Carbon::parse($ticket->due_date)->format('M d, Y') }}
                                                </span>
                                                @else
                                                <span class="text-xs italic text-gray-400">--</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium text-white shadow-sm"
                                                    style="background-color: {{ $ticket->status->color ?? '#6B7280' }}">
                                                    {{ $ticket->status->name ?? 'Unknown' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="py-12 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <div class="p-3 bg-gray-100 rounded-full dark:bg-gray-800">
                                                        <svg class="w-8 h-8 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                    <p class="mt-2 text-sm font-medium text-black dark:text-white">Tidak
                                                        ada tugas ditemukan</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <!-- Mobile Card View (Updated Look) -->
                                <div class="sm:hidden">
                                    @forelse($this->tickets as $ticket)
                                    <div
                                        class="p-4 border-b border-border-light dark:border-border-dark hover:bg-gray-50 dark:hover:bg-white/5">
                                        <div class="flex items-start justify-between mb-2">
                                            <span
                                                class="px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase bg-gray-100 text-black dark:bg-gray-800 dark:text-white">{{
                                                $ticket->uuid }}</span>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white"
                                                style="background-color: {{ $ticket->status->color ?? '#6B7280' }}">
                                                {{ $ticket->status->name ?? 'Unknown' }}
                                            </span>
                                        </div>
                                        <h4 class="text-base font-semibold text-black dark:text-white">
                                            {{ $ticket->name }}</h4>
                                        @if ($ticket->description)
                                        <p class="mt-1 text-sm text-black line-clamp-2 dark:text-white">
                                            {{ strip_tags(Str::limit($ticket->description, 100)) }}</p>
                                        @endif

                                        <div class="flex items-center mt-3 text-sm text-black dark:text-white">
                                            <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            @if ($ticket->due_date)
                                            <span
                                                class="{{ \Carbon\Carbon::parse($ticket->due_date)->isPast() ? 'text-red-600' : '' }}">
                                                {{ \Carbon\Carbon::parse($ticket->due_date)->format('d M Y') }}
                                            </span>
                                            @else
                                            <span>No date</span>
                                            @endif
                                        </div>
                                    </div>
                                    @empty
                                    <div class="py-12 text-center text-gray-600">Tidak ada data.</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Pagination -->
                            @if ($this->tickets->hasPages())
                            <div
                                class="px-6 py-4 border-t border-border-light dark:border-border-dark bg-gray-50/50 dark:bg-white/5 rounded-b-2xl">
                                {{ $this->tickets->links() }}
                            </div>
                            @endif
                        </div>

                        <!-- Activity Tab (Clean Design) -->
                        <div id="activity-tab" class="hidden tab-content">
                            <div class="p-6">
                                <h3 class="mb-4 text-lg font-bold text-black dark:text-white">Aktivitas Terbaru</h3>

                                <div
                                    class="relative pl-4 space-y-6 border-l-2 border-border-light dark:border-border-dark">
                                    @forelse($this->recentActivities as $activity)
                                    <div class="relative">
                                        <!-- Dot -->
                                        <div
                                            class="absolute -left-[25px] top-5 w-4 h-4 rounded-full bg-main-primary ring-2 ring-main-primary/50">
                                        </div>

                                        <div
                                            class="p-3 -mt-2 transition-colors rounded-xl hover:bg-main-light dark:hover:bg-white/5">
                                            <p class="text-sm text-black dark:text-gray-200">
                                                <span class="font-semibold">{{ $activity->ticket->name ?? 'Tugas'
                                                    }}</span>
                                                @if ($activity->status)
                                                berubah status ke <span class="font-medium"
                                                    style="color: {{ $activity->status->color ?? '#6B7280' }}">{{
                                                    $activity->status->name }}</span>
                                                @else
                                                telah diperbarui
                                                @endif
                                            </p>
                                            <span class="text-xs text-gray-400">{{
                                                $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="py-4 text-sm text-gray-600">Belum ada aktivitas.</div>
                                    @endforelse
                                </div>

                                @if ($this->recentActivities->hasPages())
                                <div class="pt-4 mt-6 border-t border-border-light dark:border-border-dark">
                                    {{ $this->recentActivities->links() }}
                                </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- SCRIPTS (Tetap sama persis) -->
@push('scripts')
<script>
    // ... (Script Javascript Anda tetap sama, copy paste dari kode lama Anda di sini karena ID tab-content dan JS Logic tidak saya ubah agar tetap berfungsi) ...
    document.addEventListener('DOMContentLoaded', function () {
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 300);
            }, 3000);
        }

        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.opacity = '0';
                setTimeout(() => errorMessage.remove(), 300);
            }, 5000);
        }
    });

    // Tab Management with persistence across Livewire updates
    let currentActiveTab = sessionStorage.getItem('currentActiveTab') || 'tasks';
    let tabScrollPositions = {};

    // Maintain tab state across page interactions
    function saveCurrentTab(tabName) {
        currentActiveTab = tabName;
        sessionStorage.setItem('currentActiveTab', tabName);
    }

    function getCurrentTab() {
        return sessionStorage.getItem('currentActiveTab') || 'tasks';
    }

    function switchTab(tabName) {
        if (currentActiveTab) {
            tabScrollPositions[currentActiveTab] = window.pageYOffset;
        }

        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
            tab.classList.add('hidden');
        });

        // Update simple active logic for buttons if needed, though Alpine handles the class
        const selectedTab = document.getElementById(tabName + '-tab');

        if (selectedTab) {
            selectedTab.classList.add('active');
            selectedTab.classList.remove('hidden');

            saveCurrentTab(tabName);

            if (tabScrollPositions[tabName] !== undefined) {
                setTimeout(() => {
                    window.scrollTo({
                        top: tabScrollPositions[tabName],
                        behavior: 'smooth'
                    });
                }, 50);
            }
        }
    }

    // Initialize tabs on page load
    document.addEventListener('DOMContentLoaded', function () {
        const savedTab = getCurrentTab();
        switchTab(savedTab);

        if (typeof Livewire !== 'undefined') {
            setupLivewireListeners();
        } else {
            document.addEventListener('livewire:init', setupLivewireListeners);
        }
    });

    // Handle Livewire component updates (filtering, pagination, etc.)
    document.addEventListener('livewire:morph', function () {
        const savedTab = getCurrentTab();
        setTimeout(() => { switchTab(savedTab); }, 50);
    });

    document.addEventListener('livewire:navigated', function () {
        setTimeout(() => { const activeTab = getCurrentTab(); switchTab(activeTab); }, 100);
    });

    function handlePaginationClick(event) {
        const currentScrollPos = window.pageYOffset;
        const currentTab = getCurrentTab();
        setTimeout(() => {
            switchTab(currentTab);
            setTimeout(() => {
                window.scrollTo({ top: currentScrollPos, behavior: 'instant' });
            }, 50);
        }, 100);
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('nav[role="navigation"]') || e.target.closest('.pagination')) {
            handlePaginationClick(e);
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.matches('select[wire\\:model\\.live]') || e.target.matches('input[wire\\:model\\.live]')) {
            const currentTab = getCurrentTab();
            setTimeout(() => { switchTab(currentTab); }, 150);
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.matches('input[wire\\:model\\.live\\.debounce]')) {
            const currentTab = getCurrentTab();
            setTimeout(() => { switchTab(currentTab); }, 500);
        }
    });

    function setupLivewireListeners() {
        Livewire.on('data-refreshed', () => {
            const currentTab = getCurrentTab();
            setTimeout(() => { switchTab(currentTab); }, 150);
        });
        Livewire.on('pagination-updated', () => {
            const currentTab = getCurrentTab();
            setTimeout(() => { switchTab(currentTab); }, 50);
        });
        Livewire.hook('morph.updated', ({ el, component }) => {
            const currentTab = getCurrentTab();
            setTimeout(() => { switchTab(currentTab); }, 25);
        });
    }
</script>
@endpush