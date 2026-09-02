<div class="fi-page-header">
    <div class="fi-card fi-card--gray-50 dark:fi-card--gray-900">
        <div class="fi-card-header fi-card-header--transparent">
            <h3 class="fi-card-header__title text-lg font-semibold text-gray-900 dark:text-gray-100">
                Filter Employee Performance
            </h3>
            <p class="fi-card-header__description text-sm text-gray-600 dark:text-gray-400">
                Filter berdasarkan department, posisi, project, status project, dan periode tanggal.
            </p>
        </div>

        <div class="fi-card-body">
            <form wire:submit.prevent="applyFilters" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Department --}}
                    <div>
                        <label for="filter_department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                        <select wire:model="filter_department_id" id="filter_department_id"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                            <option value="">All Departments</option>
                            @foreach($departments as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Position --}}
                    <div>
                        <label for="filter_position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position</label>
                        <select wire:model="filter_position" id="filter_position"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                            <option value="">All Positions</option>
                            @foreach($positions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Project --}}
                    <div>
                        <label for="filter_project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
                        <select wire:model="filter_project_id" id="filter_project_id"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                            <option value="">All Projects</option>
                            @foreach($projects as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Project Status --}}
                    <div>
                        <label for="filter_project_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Status</label>
                        <select wire:model="filter_project_status" id="filter_project_status"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Start Date --}}
                    <div>
                        <label for="filter_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input type="date" wire:model="filter_start_date" id="filter_start_date"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label for="filter_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                        <input type="date" wire:model="filter_end_date" id="filter_end_date"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                    </div>

                    {{-- Period --}}
                    <div>
                        <label for="filter_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project Period</label>
                        <select wire:model="filter_period" id="filter_period"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow shadow-sm">
                            <option value="">Custom Period</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 3 Months</option>
                            <option value="180">Last 6 Months</option>
                            <option value="365">Last 1 Year</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-start gap-4 pt-4 mt-auto border-t border-gray-200 dark:border-gray-700">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-60 transition-all"
                        wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Search
                    </button>

                    <button type="button" wire:click="resetFilters"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-gray-800 bg-gray-200 rounded-lg shadow hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 disabled:opacity-60 transition-all"
                        wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>