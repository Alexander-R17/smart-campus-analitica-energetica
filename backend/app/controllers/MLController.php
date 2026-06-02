<?php

class MLController
{
    public function ejecutarColab(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';

        $csvOriginal = $config['UPLOAD_CSV_PATH'];
        $csvProcesado = $config['PROCESSED_CSV_PATH'];
        $colabUrl = rtrim($config['COLAB_API_URL'], '/') . '/predict';

        if (!file_exists($csvOriginal)) {
            json_response([
                'ok' => false,
                'error' => 'No existe CSV cargado. Primero sube el archivo en el paso 1.'
            ], 400);
        }

        $response = $this->sendCsvToColab($colabUrl, $csvOriginal);

        if (!$response['ok']) {
            if (!empty($config['LOCAL_FALLBACK'])) {
                $this->localFallback($csvOriginal, $csvProcesado);
                $rows = $this->saveToMySQL($csvProcesado);
                json_response([
                    'ok' => true,
                    'message' => 'Colab no respondió; se usó fallback local básico.',
                    'rows_mysql' => $rows,
                    'fallback' => true
                ]);
            }

            json_response($response, 500);
        }

        if (!is_dir(dirname($csvProcesado))) {
            mkdir(dirname($csvProcesado), 0777, true);
        }

        file_put_contents($csvProcesado, $response['csv']);
        $rows = $this->saveToMySQL($csvProcesado);

        json_response([
            'ok' => true,
            'message' => 'Capa IA y Capa Semántica procesadas correctamente.',
            'csv_procesado' => $csvProcesado,
            'rows_mysql' => $rows,
            'resumen' => $response['resumen'] ?? []
        ]);
    }

    public function status(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $repo = new FactConsumoRepository();

        json_response([
            'ok' => true,
            'uploaded_csv_exists' => file_exists($config['UPLOAD_CSV_PATH']),
            'processed_csv_exists' => file_exists($config['PROCESSED_CSV_PATH']),
            'mysql_rows' => $repo->countRows(),
            'colab_api_url' => $config['COLAB_API_URL']
        ]);
    }

    private function sendCsvToColab(string $colabUrl, string $csvOriginal): array
    {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'error' => 'La extensión cURL de PHP no está activa en XAMPP.'
            ];
        }

        $postData = [
            'csv' => new CURLFile($csvOriginal, 'text/csv', 'dataset.csv')
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $colabUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 180,
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
            return [
                'ok' => false,
                'error' => 'Error al conectar con Google Colab: ' . $curlError
            ];
        }

        $data = json_decode($raw, true);

        if (!$data || empty($data['ok'])) {
            return [
                'ok' => false,
                'error' => 'Respuesta inválida de Colab. HTTP ' . $httpCode,
                'raw' => substr((string)$raw, 0, 500)
            ];
        }

        return $data;
    }

    private function saveToMySQL(string $csvProcesado): int
    {
        $repo = new FactConsumoRepository();
        return $repo->importCsv($csvProcesado);
    }

    private function localFallback(string $csvOriginal, string $csvProcesado): void
    {
        $input = fopen($csvOriginal, 'r');
        if (!$input) {
            throw new RuntimeException('No se pudo abrir CSV original para fallback.');
        }

        if (!is_dir(dirname($csvProcesado))) {
            mkdir(dirname($csvProcesado), 0777, true);
        }

        $output = fopen($csvProcesado, 'w');
        $headers = fgetcsv($input);

        $extra = [
            'nombre_mes', 'periodo', 'franja_horaria', 'eficiencia_energetica_pct',
            'riesgo_energetico_indice', 'pred_consumo_kwh', 'sobreconsumo_real',
            'riesgo_sobreconsumo_prob', 'riesgo_sobreconsumo_pred'
        ];

        fputcsv($output, array_merge($headers, $extra));

        while (($row = fgetcsv($input)) !== false) {
            $data = array_combine($headers, $row);
            $mes = (int)($data['mes'] ?? 1);
            $hora = (int)($data['hora'] ?? 0);
            $consumo = (float)($data['consumo_kwh'] ?? 0);
            $riesgo = $consumo >= 500 ? 'Alto' : ($consumo >= 350 ? 'Medio' : 'Bajo');
            $prob = $riesgo === 'Alto' ? 0.88 : ($riesgo === 'Medio' ? 0.55 : 0.20);

            $meses = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];
            $franja = ($hora >= 6 && $hora <= 11) ? 'Mañana' : (($hora >= 12 && $hora <= 17) ? 'Tarde' : 'Noche');

            fputcsv($output, array_merge($row, [
                $meses[$mes] ?? 'Sin mes',
                ($data['anio'] ?? '2026') . '-' . str_pad((string)$mes, 2, '0', STR_PAD_LEFT),
                $franja,
                0,
                $riesgo,
                round($consumo * 1.05, 2),
                $riesgo === 'Alto' ? 1 : 0,
                $prob,
                $riesgo
            ]));
        }

        fclose($input);
        fclose($output);
    }
}
