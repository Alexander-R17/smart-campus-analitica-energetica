<?php

class PowerBIController
{
    public function openReport(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $report = realpath($config['POWERBI_REPORT_PATH']);

        if (!$report || !file_exists($report)) {
            json_response([
                'ok' => false,
                'error' => 'No se encontró el archivo Power BI. Coloca tu .pbix en powerbi/report/smartcampus_reporte.pbix'
            ], 404);
        }

        if (stripos(PHP_OS, 'WIN') === 0) {
            pclose(popen('start "" "' . $report . '"', 'r'));
        } else {
            exec('xdg-open ' . escapeshellarg($report) . ' > /dev/null 2>&1 &');
        }

        json_response([
            'ok' => true,
            'message' => 'Power BI Desktop fue solicitado. Si no abre, abre manualmente el archivo .pbix.',
            'report' => $report
        ]);
    }

    public function downloadProcessedCsv(): void
    {
        $config = require __DIR__ . '/../config/ExternalServices.php';
        $csv = $config['PROCESSED_CSV_PATH'];

        if (!file_exists($csv)) {
            json_response([
                'ok' => false,
                'error' => 'Todavía no existe CSV procesado.'
            ], 404);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="smartcampus_powerbi_dataset_predicciones.csv"');
        readfile($csv);
        exit;
    }
}
