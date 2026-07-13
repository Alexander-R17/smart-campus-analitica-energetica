<?php

class MLController
{
    /*
     * Mantengo el nombre ejecutarColab() porque tu index.php todavía llama:
     * (new MLController())->ejecutarColab();
     *
     * Pero internamente ya NO usa Google Colab.
     * Ahora llama al servicio IA en Render:
     * https://smart-campus-ia.onrender.com/process
     */
    public function ejecutarColab(): void
    {
        $this->ejecutarIA();
    }

    public function ejecutarIA(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';

        $aiBaseUrl = rtrim((string)($config['AI_SERVICE_URL'] ?? ''), '/');

        if ($aiBaseUrl === '') {
            $aiBaseUrl = rtrim((string)($config['COLAB_API_URL'] ?? ''), '/');
        }

        if (
            $aiBaseUrl === '' ||
            str_contains($aiBaseUrl, 'TU_URL_NGROK') ||
            str_contains($aiBaseUrl, 'PEGAR') ||
            str_contains($aiBaseUrl, 'AQUI')
        ) {
            json_response([
                'ok' => false,
                'error' => 'Falta configurar AI_SERVICE_URL con la URL pública del servicio IA en Render.',
                'expected' => 'https://smart-campus-ia.onrender.com'
            ], 500);
            return;
        }

        $processUrl = $aiBaseUrl . '/process';

        $result = $this->postJson($processUrl, []);

        if (!$result['ok']) {
            json_response([
                'ok' => false,
                'error' => 'No se pudo conectar con el servicio IA en Render.',
                'service_url' => $aiBaseUrl,
                'endpoint' => $processUrl,
                'detail' => $result['error'] ?? 'Error desconocido'
            ], 500);
            return;
        }

        $body = $result['body'];

        if (!is_array($body)) {
            json_response([
                'ok' => false,
                'error' => 'Respuesta inválida del servicio IA.',
                'service_url' => $aiBaseUrl,
                'raw_response' => $result['raw'] ?? null
            ], 500);
            return;
        }

        if (!($body['ok'] ?? false)) {
            json_response([
                'ok' => false,
                'error' => $body['error'] ?? $body['message'] ?? 'El servicio IA respondió con error.',
                'service_url' => $aiBaseUrl,
                'response' => $body
            ], 500);
            return;
        }

        json_response([
            'ok' => true,
            'message' => 'Proceso IA ejecutado correctamente desde Render. Las predicciones fueron guardadas en Supabase.',
            'ai_provider' => 'Render Flask API',
            'service_url' => $aiBaseUrl,
            'endpoint' => $processUrl,
            'registros_leidos' => $body['registros_leidos'] ?? null,
            'registros_insertados' => $body['registros_insertados'] ?? null,
            'insert_status' => $body['insert_status'] ?? null,
            'dashboard_url' => $config['STREAMLIT_DASHBOARD_URL'] ?? null
        ]);
    }

    private function postJson(string $url, array $payload = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            return [
                'ok' => false,
                'error' => $curlError ?: 'Error desconocido de cURL',
                'http_code' => $httpCode
            ];
        }

        $decoded = json_decode($raw, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'ok' => false,
                'error' => 'HTTP ' . $httpCode . ': ' . $raw,
                'http_code' => $httpCode,
                'raw' => $raw
            ];
        }

        return [
            'ok' => true,
            'http_code' => $httpCode,
            'body' => $decoded,
            'raw' => $raw
        ];
    }
}