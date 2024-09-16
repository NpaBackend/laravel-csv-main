<?php

namespace Npa\LaravelCsv;

if (! function_exists('Npa\LaravelCsv\csv_view_path')) {
    /**
     * Get the evaluated view content from the livewire view
     */
    function csv_view_path(string|null $view): string
    {
        return 'laravel-csv::livewire.'.config('laravel_csv.layout').'.'.$view;
    }
}
