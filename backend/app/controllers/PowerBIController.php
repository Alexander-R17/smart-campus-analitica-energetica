<?php

class PowerBIController
{
    public function openReport(): void
    {
        json_response([
            'ok' => false,
            'message' => 'Power BI local fue reemplazado por Looker Studio. Usa el paso 7 para abrir el dashboard cloud.',
            'replacement' => 'Looker Studio'
        ], 410);
    }

    public function downloadProcessedCsv(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $csv = $config['PROCESSED_CSV_PATH'];

        if (!file_exists($csv)) {
            json_response([
                'ok' => false,
                'error' => 'No existe CSV local porque la arquitectura final trabaja con Supabase + Google Sheets. Activa KEEP_LOCAL_UPLOAD_BACKUP solo para respaldo técnico.'
            ], 404);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="smartcampus_looker_dataset_predicciones.csv"');
        readfile($csv);
        exit;
    }
}
