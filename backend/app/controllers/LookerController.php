<?php

class LookerController
{
    public function config(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $url = trim((string)($config['LOOKER_STUDIO_URL'] ?? ''));

        $configured = $url !== ''
            && stripos($url, 'PEGAR_URL_PUBLICA_LOOKER_STUDIO') === false
            && filter_var($url, FILTER_VALIDATE_URL);

        json_response([
            'ok' => (bool)$configured,
            'url' => $configured ? $url : null,
            'message' => $configured
                ? 'Looker Studio configurado.'
                : 'Configura LOOKER_STUDIO_URL en backend/app/config/ExternalServices.php.'
        ], $configured ? 200 : 400);
    }
}
