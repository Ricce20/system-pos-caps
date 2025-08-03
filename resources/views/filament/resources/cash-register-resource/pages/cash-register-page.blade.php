<x-filament-panels::page>
    @if ($opened)
        @livewire('Scaner-Component',['record' => $record])
    @endif

</x-filament-panels::page>
