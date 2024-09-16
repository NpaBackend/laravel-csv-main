<button
        {{ $attributes->merge(['type' => 'button']) }}
        x-data
        x-on:click="$dispatchTo('toggle')">
    {{ $slot }}
</button>
