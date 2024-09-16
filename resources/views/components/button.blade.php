<button
        {{ $attributes->merge(['type' => 'button']) }}
        x-data
        x-on:click="Livewire.emit('toggle')">
    {{ $slot }}
</button>
