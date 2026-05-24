<x-filament-panels::page>

    <div class="space-y-6">

        {{-- HEADER --}}
        <div>
            <h2 class="text-2xl font-bold tracking-tight">
                Finance Dashboard
            </h2>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Financial overview, receivables, payables, and reimbursement activities.
            </p>
        </div>

        {{-- WIDGETS --}}
        <x-filament-widgets::widgets
            :widgets="$this->getHeaderWidgets()"
            :columns="$this->getHeaderWidgetsColumns()"
        />

    </div>

</x-filament-panels::page>
