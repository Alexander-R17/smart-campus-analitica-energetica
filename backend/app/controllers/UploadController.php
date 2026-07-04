<?php

class UploadController
{
    public function uploadCsv(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $repo = new CloudWarehouseRepository();

        $files = $this->collectUploadedCsvFiles();
        if (!$files) {
            json_response([
                'ok' => false,
                'error' => 'No se recibió ningún archivo CSV válido.'
            ], 400);
        }

        $fileNames = array_map(fn($f) => $f['name'], $files);
        $batchId = $repo->createBatch($fileNames);
        $_SESSION['current_batch_id'] = $batchId;

        $rows = 0;
        foreach ($files as $file) {
            $rows += $repo->importCsvToStaging($file['tmp_name'], $batchId, $file['name']);
        }

        if (strtolower((string)($config['KEEP_LOCAL_UPLOAD_BACKUP'] ?? 'false')) === 'true') {
            $this->buildConsolidatedCsv($files, $config['UPLOAD_CSV_PATH']);
        }

        json_response([
            'ok' => true,
            'message' => 'CSV cargado en la Supabase PostgreSQL cloud.',
            'batch_id' => $batchId,
            'archivos_recibidos' => count($files),
            'archivos' => $fileNames,
            'registros_estimados' => $rows,
            'supabase_database' => true,
            'siguiente' => 'Paso 2: validar origen y calidad de datos en staging en Supabase.'
        ]);
    }

    private function collectUploadedCsvFiles(): array
    {
        $files = [];

        if (isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
            $this->validateCsvName($_FILES['csv']['name']);
            $files[] = [
                'name' => $_FILES['csv']['name'],
                'tmp_name' => $_FILES['csv']['tmp_name'],
            ];
        }

        if (isset($_FILES['csv_files']) && is_array($_FILES['csv_files']['name'])) {
            $totalFiles = count($_FILES['csv_files']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['csv_files']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $this->validateCsvName($_FILES['csv_files']['name'][$i]);
                $files[] = [
                    'name' => $_FILES['csv_files']['name'][$i],
                    'tmp_name' => $_FILES['csv_files']['tmp_name'][$i],
                ];
            }
        }

        return $files;
    }

    private function buildConsolidatedCsv(array $files, string $destino): string
    {
        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0777, true);
        }

        $out = fopen($destino, 'w');
        if (!$out) {
            throw new RuntimeException('No se pudo crear CSV temporal consolidado.');
        }

        $headerWritten = false;
        $canonicalHeader = null;
        $rows = 0;

        foreach ($files as $file) {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                continue;
            }

            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                continue;
            }

            $normalizedHeader = array_map(fn($h) => $this->normalizeHeader((string)$h), $header);
            if (!$headerWritten) {
                fputcsv($out, $normalizedHeader);
                $canonicalHeader = $normalizedHeader;
                $headerWritten = true;
            } elseif ($canonicalHeader !== $normalizedHeader) {
                fclose($handle);
                throw new RuntimeException('Los CSV no tienen la misma estructura de columnas. Revisa: ' . $file['name']);
            }

            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue;
                }
                fputcsv($out, $row);
                $rows++;
            }

            fclose($handle);
        }

        fclose($out);

        if (!$headerWritten || $rows === 0) {
            throw new RuntimeException('Los CSV no tienen datos válidos.');
        }

        return $destino;
    }

    private function validateCsvName(string $fileName): void
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            json_response([
                'ok' => false,
                'error' => 'Para esta versión cloud usa archivos .csv. Si tienes Excel, guárdalo como CSV antes de subirlo.'
            ], 400);
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(strtolower($header));
        $header = str_replace([' ', '-', '.', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['_', '_', '_', 'a', 'e', 'i', 'o', 'u', 'n'], $header);
        return preg_replace('/[^a-z0-9_]/', '', $header) ?: 'columna';
    }
}
