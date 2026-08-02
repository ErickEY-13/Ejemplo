<?php

declare(strict_types=1);

namespace App\Modules\Persons\Jobs;

use App\Modules\Persons\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImportPersonsCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $filePath,
        public readonly string $jobId
    ) {}

    public function handle(): void
    {
        $path = Storage::disk('local')->path($this->filePath);

        if (!file_exists($path)) {
            $this->updateProgress('error', 0, 0);
            return;
        }

        // Leer todas las líneas para saber el total
        $lines = file($path);
        if (!$lines || count($lines) <= 1) {
            $this->updateProgress('completed', 0, 0);
            Storage::disk('local')->delete($this->filePath);
            return;
        }

        $totalRows = count($lines) - 1; // Menos la cabecera
        $this->updateProgress('processing', 0, $totalRows);

        $handle = fopen($path, 'r');
        
        // Detectar separador (coma o punto y coma)
        $firstLine = $lines[0];
        $separator = strpos($firstLine, ';') !== false ? ';' : ',';

        $header = fgetcsv($handle, 1000, $separator);
        if (!$header) {
            $this->updateProgress('error', 0, 0);
            return;
        }

        // Limpiar BOM (Byte Order Mark) de la primera columna si existe
        $header[0] = preg_replace('/[\xEF\xBB\xBF]/', '', $header[0]);
        $header = array_map('trim', $header);

        $progress = 0;

        while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
            if (count($row) !== count($header)) {
                continue; // Ignorar filas mal formadas
            }

            $data = array_combine($header, $row);

            // Validar campos mínimos
            if (!empty($data['document_number']) && !empty($data['first_name'])) {
                Person::create([
                    'document_type' => $data['document_type'] ?? 'national_id',
                    'document_number' => $data['document_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'] ?? '',
                    'email' => !empty($data['email']) ? $data['email'] : null,
                    'phone' => !empty($data['phone']) ? $data['phone'] : null,
                    'gender' => $data['gender'] ?? 'undisclosed',
                    'is_active' => true,
                ]);
            }

            $progress++;

            // Retardo artificial para la demo (100ms)
            usleep(100000);

            $this->updateProgress('processing', $progress, $totalRows);
        }

        fclose($handle);
        Storage::disk('local')->delete($this->filePath);

        $this->updateProgress('completed', $progress, $totalRows);
    }

    private function updateProgress(string $status, int $progress, int $total): void
    {
        Cache::put("import_progress_{$this->jobId}", [
            'status' => $status,
            'progress' => $progress,
            'total' => $total,
        ], 3600); // 1 hora
    }
}
