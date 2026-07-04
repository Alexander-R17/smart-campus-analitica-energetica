<?php

class StreamlitController
{
    public function config(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $url = trim((string)($config['STREAMLIT_DASHBOARD_URL'] ?? ''));

        $configured = $url !== ''
            && stripos($url, 'PEGAR_URL_PUBLICA_STREAMLIT') === false
            && filter_var($url, FILTER_VALIDATE_URL);

        json_response([
            'ok' => (bool)$configured,
            'url' => $configured ? $url : null,
            'message' => $configured
                ? 'Streamlit Community Cloud configurado.'
                : 'Configura STREAMLIT_DASHBOARD_URL en backend/app/config/ExternalServices.php.'
        ], $configured ? 200 : 400);
    }
}
