<?php

class MLController
{
    public function ejecutarColab(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $repo = new CloudWarehouseRepository();
        $batchId = $repo->requireCurrentBatchId();

        $colabBase = rtrim((string)$config['COLAB_API_URL'], '/');
        if ($colabBase === '' || str_contains($colabBase, 'TU_URL_NGROK')) {
            json_response([
                'ok' => false,
                'error' => 'Falta configurar COLAB_API_URL con la URL pública de ngrok/Colab.'
            ], 400);
        }

        // Modo final recomendado: el backend exporta desde Supabase y envía CSV a Colab.
        // Así Colab no necesita credenciales de Supabase.
        $response = $this->sendSupabaseWarehouseCsvToColab($colabBase . '/predict', $repo, $batchId);

        if (!$response['ok']) {
            json_response($response, 500);
        }

        $csvText = (string)($response['csv'] ?? '');
        if ($csvText === '') {
            json_response([
                'ok' => false,
                'error' => 'Colab respondió correctamente, pero no devolvió CSV predictivo.'
            ], 500);
        }

        $rowsPred = $repo->importPredictionsCsv($csvText, $batchId);
        $sheetsResult = $this->sendCsvToGoogleSheets($config, $csvText, $response['resumen'] ?? [], $batchId);

        if (strtolower((string)($config['KEEP_LOCAL_UPLOAD_BACKUP'] ?? 'false')) === 'true') {
            $csvProcesado = $config['PROCESSED_CSV_PATH'];
            if (!is_dir(dirname($csvProcesado))) {
                mkdir(dirname($csvProcesado), 0777, true);
            }
            file_put_contents($csvProcesado, $csvText);
        }

        json_response([
            'ok' => true,
            'message' => 'Capa IA procesada desde Supabase y lista para Looker Studio.',
            'batch_id' => $batchId,
            'colab_input_mode' => 'backend_export',
            'rows_cloud_pred' => $rowsPred,
            'google_sheets' => $sheetsResult,
            'resumen' => $response['resumen'] ?? []
        ]);
    }

    public function status(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $repo = new CloudWarehouseRepository();
        $batchId = $repo->currentBatchId();
        $status = $repo->warehouseStatus($batchId);

        json_response([
            'ok' => true,
            'batch_id' => $batchId,
            'supabase_database' => true,
            'warehouse' => $status,
            'google_sheets_configured' => $this->isGoogleSheetsConfigured($config),
            'looker_configured' => !empty($config['LOOKER_STUDIO_URL']) && stripos($config['LOOKER_STUDIO_URL'], 'PEGAR_URL_PUBLICA_LOOKER_STUDIO') === false,
            'colab_api_url' => $config['COLAB_API_URL'],
            'colab_input_mode' => 'backend_export'
        ]);
    }

    private function sendSupabaseWarehouseCsvToColab(string $colabUrl, CloudWarehouseRepository $repo, int $batchId): array
    {
        $csv = $repo->exportFactCsv($batchId);
        $temp = tmpfile();
        if (!$temp) {
            return ['ok' => false, 'error' => 'No se pudo crear archivo temporal para enviar a Colab.'];
        }
        fwrite($temp, $csv);
        $meta = stream_get_meta_data($temp);
        $path = $meta['uri'];

        $result = $this->postMultipartToColab($colabUrl, [
            'csv' => new CURLFile($path, 'text/csv', 'dw_supabase_batch_' . $batchId . '.csv'),
            'source' => 'supabase_postgresql_snowflake',
            'batch_id' => (string)$batchId,
        ]);

        fclose($temp);
        return $result;
    }

    private function postMultipartToColab(string $colabUrl, array $postData): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'La extensión cURL de PHP no está activa.'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $colabUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'ngrok-skip-browser-warning: true'
            ]
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['ok' => false, 'error' => 'Error al conectar con Google Colab: ' . $curlError];
        }

        $data = json_decode((string)$raw, true);
        if (!$data || empty($data['ok'])) {
            return [
                'ok' => false,
                'error' => 'Respuesta inválida de Colab. HTTP ' . $httpCode,
                'raw' => substr((string)$raw, 0, 700)
            ];
        }
        return $data;
    }

    private function isGoogleSheetsConfigured(array $config): bool
    {
        $url = trim((string)($config['GOOGLE_SHEETS_WEBAPP_URL'] ?? ''));
        return $url !== ''
            && stripos($url, 'PEGAR_URL_APPS_SCRIPT') === false
            && (bool)filter_var($url, FILTER_VALIDATE_URL);
    }

    private function sendCsvToGoogleSheets(array $config, string $csvText, array $resumen = [], ?int $batchId = null): array
    {
        if (!$this->isGoogleSheetsConfigured($config)) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'Google Sheets no configurado. Pega GOOGLE_SHEETS_WEBAPP_URL en ExternalServices.php.'
            ];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'message' => 'La extensión cURL de PHP no está activa.'];
        }

        $payload = json_encode([
            'token' => (string)($config['GOOGLE_SHEETS_TOKEN'] ?? ''),
            'dataset' => 'smartcampus_supabase_predicciones',
            'batch_id' => $batchId,
            'updated_at' => date('c'),
            'resumen' => $resumen,
            'csv' => $csvText
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['GOOGLE_SHEETS_WEBAPP_URL'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json'
            ]
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return ['ok' => false, 'message' => 'Error al enviar datos a Google Sheets: ' . $curlError];
        }

        $data = json_decode((string)$raw, true);
        if (!$data || empty($data['ok'])) {
            return [
                'ok' => false,
                'message' => 'Respuesta inválida de Google Sheets. HTTP ' . $httpCode,
                'raw' => substr((string)$raw, 0, 500)
            ];
        }
        return $data;
    }
}
