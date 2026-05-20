<x-filament-panels::page>
    {{ $this->filtersForm }}

    <x-filament-widgets::widgets
        :widgets="$this->getVisibleWidgets()"
        :columns="12"
    />
</x-filament-panels::page>