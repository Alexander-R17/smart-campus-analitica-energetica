<?php

class UploadController
{
    public function uploadCsv(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $destino = $config['UPLOAD_CSV_PATH'];

        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0777, true);
        }

        // Caso 1: un solo CSV, compatible con versiones anteriores.
        if (isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
            $this->validateCsvName($_FILES['csv']['name']);

            if (!move_uploaded_file($_FILES['csv']['tmp_name'], $destino)) {
                json_response([
                    'ok' => false,
                    'error' => 'No se pudo guardar el CSV en storage/uploads.'
                ], 500);
            }

            $lineas = $this->countCsvRows($destino);
            json_response([
                'ok' => true,
                'message' => 'CSV cargado correctamente.',
                'archivo' => basename($destino),
                'archivos_recibidos' => 1,
                'registros_estimados' => $lineas
            ]);
        }

        // Caso 2: varios CSV. Se unifican en ultimo_dataset.csv.
        if (isset($_FILES['csv_files']) && is_array($_FILES['csv_files']['name'])) {
            $files = $_FILES['csv_files'];
            $totalFiles = count($files['name']);

            if ($totalFiles < 1) {
                json_response([
                    'ok' => false,
                    'error' => 'No se recibieron archivos CSV.'
                ], 400);
            }

            $out = fopen($destino, 'w');
            if (!$out) {
                json_response([
                    'ok' => false,
                    'error' => 'No se pudo crear el CSV consolidado.'
                ], 500);
            }

            $headerWritten = false;
            $rows = 0;
            $validFiles = 0;

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $this->validateCsvName($files['name'][$i]);
                $handle = fopen($files['tmp_name'][$i], 'r');
                if (!$handle) {
                    continue;
                }

                $header = fgetcsv($handle);
                if (!$header) {
                    fclose($handle);
                    continue;
                }

                if (!$headerWritten) {
                    fputcsv($out, $header);
                    $headerWritten = true;
                }

                while (($row = fgetcsv($handle)) !== false) {
                    // Evita filas vacías.
                    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                        continue;
                    }
                    fputcsv($out, $row);
                    $rows++;
                }

                fclose($handle);
                $validFiles++;
            }

            fclose($out);

            if (!$headerWritten || $rows === 0) {
                json_response([
                    'ok' => false,
                    'error' => 'Los CSV no tienen datos válidos.'
                ], 400);
            }

            json_response([
                'ok' => true,
                'message' => 'CSV consolidado correctamente.',
                'archivo' => basename($destino),
                'archivos_recibidos' => $validFiles,
                'registros_estimados' => $rows
            ]);
        }

        json_response([
            'ok' => false,
            'error' => 'No se recibió ningún archivo CSV válido.'
        ], 400);
    }

    private function validateCsvName(string $fileName): void
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            json_response([
                'ok' => false,
                'error' => 'Todos los archivos deben tener extensión .csv'
            ], 400);
        }
    }

    private function countCsvRows(string $path): int
    {
        if (!file_exists($path)) {
            return 0;
        }
        return max(0, count(file($path)) - 1);
    }
}
