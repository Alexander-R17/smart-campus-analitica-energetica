<?php

class SupabaseRestClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $schema;

    public function __construct(?array $config = null)
    {
        $config = $config ?: require __DIR__ . '/ExternalServices.php';
        $this->baseUrl = rtrim((string)($config['SUPABASE_URL'] ?? ''), '/');
        $this->apiKey = (string)($config['SUPABASE_API_KEY'] ?? '');
        $this->schema = (string)($config['SUPABASE_SCHEMA'] ?? 'public');

        if ($this->baseUrl === '' || str_contains($this->baseUrl, 'TU-PROYECTO')) {
            throw new RuntimeException('Falta configurar SUPABASE_URL en backend/app/config/ExternalServices.php.');
        }
        if ($this->apiKey === '' || str_contains($this->apiKey, 'PEGAR_SUPABASE')) {
            throw new RuntimeException('Falta configurar SUPABASE_API_KEY en backend/app/config/ExternalServices.php.');
        }
    }

    public function select(string $table, array $query = []): array
    {
        return $this->request('GET', $table, null, $query);
    }

    public function selectAll(string $table, array $query = [], int $pageSize = 1000, int $maxRows = 100000): array
    {
        $all = [];
        $offset = 0;
        while ($offset < $maxRows) {
            $pageQuery = $query;
            $pageQuery['limit'] = (string)$pageSize;
            $pageQuery['offset'] = (string)$offset;
            $rows = $this->select($table, $pageQuery);
            $all = array_merge($all, $rows);
            $count = count($rows);
            if ($count < $pageSize || $count === 0) {
                break;
            }
            $offset += $pageSize;
        }
        return $all;
    }

    public function insert(string $table, array $rows, bool $returnRepresentation = true): array
    {
        if (empty($rows)) {
            return [];
        }
        $headers = ['Prefer: ' . ($returnRepresentation ? 'return=representation' : 'return=minimal')];
        return $this->request('POST', $table, array_values($rows), [], $headers);
    }

    public function upsert(string $table, array $rows, string $onConflict, bool $returnRepresentation = false): array
    {
        if (empty($rows)) {
            return [];
        }
        $prefer = 'resolution=merge-duplicates,' . ($returnRepresentation ? 'return=representation' : 'return=minimal');
        return $this->request('POST', $table, array_values($rows), ['on_conflict' => $onConflict], ['Prefer: ' . $prefer]);
    }

    public function update(string $table, array $values, array $filters): array
    {
        return $this->request('PATCH', $table, $values, $filters, ['Prefer: return=representation']);
    }

    public function delete(string $table, array $filters): array
    {
        return $this->request('DELETE', $table, null, $filters, ['Prefer: return=minimal']);
    }

    public function rpc(string $functionName, array $payload = []): array
    {
        return $this->request('POST', 'rpc/' . $functionName, $payload, []);
    }

    public function count(string $table, array $filters = []): int
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP no está activa.');
        }
        $query = array_merge(['select' => '*'], $filters);
        $url = $this->baseUrl . '/rest/v1/' . ltrim($table, '/') . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
            'Prefer: count=exact',
            'Range: 0-0',
            'Accept-Profile: ' . $this->schema,
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        if ($error) {
            throw new RuntimeException('Error cURL Supabase count: ' . $error);
        }
        if ($httpCode >= 400) {
            throw new RuntimeException('Supabase count HTTP ' . $httpCode . ': ' . substr((string)$raw, 0, 500));
        }
        $headerText = substr((string)$raw, 0, $headerSize);
        if (preg_match('/content-range:\s*[^\/]+\/(\d+|\*)/i', $headerText, $m) && $m[1] !== '*') {
            return (int)$m[1];
        }
        return 0;
    }

    private function request(string $method, string $path, $body = null, array $query = [], array $extraHeaders = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP no está activa. Actívala en XAMPP/php.ini.');
        }

        $url = $this->baseUrl . '/rest/v1/' . ltrim($path, '/');
        if ($query) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = array_merge([
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'Accept-Profile: ' . $this->schema,
            'Content-Profile: ' . $this->schema,
        ], $extraHeaders);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException('Error cURL Supabase: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new RuntimeException('Supabase respondió HTTP ' . $httpCode . ': ' . substr((string)$raw, 0, 1200));
        }

        if ($raw === '' || $raw === null) {
            return [];
        }

        $decoded = json_decode((string)$raw, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Respuesta Supabase no es JSON válido: ' . substr((string)$raw, 0, 500));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
