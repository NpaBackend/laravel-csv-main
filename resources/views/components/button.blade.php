<button
        {{ $attributes->merge(['type' => 'button']) }}
        x-data
        x-on:click="Livewire.emitTo('csv-importer', 'toggle')">
    {{ $slot }}
</button>
