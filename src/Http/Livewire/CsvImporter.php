<?php

namespace Npa\LaravelCsv\Http\Livewire;

use Npa\LaravelCsv\Concerns;
use function Npa\LaravelCsv\csv_view_path;
use Npa\LaravelCsv\Facades\LaravelCsv;
use Npa\LaravelCsv\Jobs\ImportCsv;
use Npa\LaravelCsv\Utilities\ChunkIterator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;

class CsvImporter extends Component
{
    use WithFileUploads;
    use Concerns\InteractsWithColumns;
    use Concerns\HasCsvProperties;

    public string $model = '';
    public bool $open = false;
    public ?object $file = null;
    public array $columnsToMap = [];
    public array $requiredColumns = [];
    public array $columnLabels = [];
    public array $fileHeaders = [];
    public int $fileRowCount = 0;

    protected array $exceptions = [
        'model', 'columnsToMap', 'open',
        'columnLabels', 'requiredColumns',
    ];

    protected array $listeners = [
        'toggle',
    ];

    public function mount(): void
    {
        // Map and convert the columnsToMap property into an associative array
        $this->columnsToMap = $this->mapThroughColumns();

        // Map and convert the requiredColumns property into a key-value array
        $this->columnLabels = $this->mapThroughColumnLabels();

        // Map and convert the requiredColumns property into a key-value array of required values
        $this->requiredColumns = $this->mapThroughRequiredColumns();
    }

    public function updatedFile($file): void
    {
        $this->validateOnly('file');

        $this->setCsvProperties();

        $this->resetValidation();
    }

    public function import(): void
    {
        $this->validate();

        $this->importCsv();

        $this->resetExcept($this->exceptions);

        $this->emit('handle-imports', 'imports.refresh');
    }

    public function toggle(): void
    {
        $this->open = !$this->open;
    }

    public function render()
    {
        return view(csv_view_path('csv-importer'), [
            'fileSize' => LaravelCsv::formatFileSize(
                config('laravel_csv.file_upload_size', 20000)
            ),
        ]);
    }

    protected function validationAttributes(): array
    {
        return $this->columnLabels;
    }

    protected function rules(): array
    {
        return [
                'file' => 'required|file|mimes:csv,txt|max:' . config('laravel_csv.file_upload_size', '20000'),
            ] + $this->requiredColumns;
    }

    protected function setCsvProperties(): void
    {
        $csvProperties = $this->handleCsvProperties();

        if (! $csvProperties instanceof MessageBag) {
            [$this->fileHeaders, $this->fileRowCount] = $csvProperties;
            return;
        }

        $this->withValidator(function (Validator $validator) use ($csvProperties) {
            $validator->after(function ($validator) use ($csvProperties) {
                $validator->errors()->merge($csvProperties->getMessages());
            });
        })->validate();
    }

    protected function importCsv(): void
    {
        $import = $this->createNewImport();
        $chunks = (new ChunkIterator($this->csvRecords->getIterator(), 10))->get();

        $jobs = collect($chunks)
            ->map(
                fn ($chunk) => new ImportCsv(
                    $import,
                    $this->model,
                    $chunk,
                    $this->columnsToMap
                )
            );

        Bus::batch($jobs)
            ->name('import-csv')
            ->finally(
                fn () => $import->touch('completed_at')
            )
            ->dispatch();
    }

    protected function createNewImport()
    {
        /** @var \Npa\LaravelCsv\Tests\Models\User */
        $user = auth()->user();

        return $user->imports()->create([
            'model' => $this->model,
            'file_path' => $this->file->getRealPath(),
            'file_name' => $this->file->getClientOriginalName(),
            'total_rows' => $this->fileRowCount,
        ]);
    }
}
