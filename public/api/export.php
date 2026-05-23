<?php
session_start();
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}
require_once __DIR__ . '/../../app/models/SmartCampusModel.php';
$model = new SmartCampusModel();
$format = $_GET['format'] ?? 'powerbi';
$filters = [
    'anio' => $_GET['anio'] ?? '',
    'mes' => $_GET['mes'] ?? '',
    'edificio' => $_GET['edificio'] ?? '',
    'tipo_ambiente' => $_GET['tipo_ambiente'] ?? '',
    'riesgo' => $_GET['riesgo'] ?? '',
    'hora_desde' => $_GET['hora_desde'] ?? '',
    'hora_hasta' => $_GET['hora_hasta'] ?? '',
    'min_consumo' => $_GET['min_consumo'] ?? '',
];

if ($format === 'powerbi') {
    $rows = $model->getWarehouseRows(10000, $filters);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="smart_campus_powerbi_dataset_filtrado.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, [
        'id_fact','id_tiempo','anio','mes','hora','dia_semana','id_edificio','edificio','tipo_edificio','id_ambiente','ambiente','tipo_ambiente','capacidad','ocupacion',
        'temperatura','demanda_pico_kw','factor_potencia','consumo_kwh','eficiencia','riesgo_sobreconsumo'
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id_fact'] ?? '', $r['id_tiempo'] ?? '', $r['anio'] ?? '', $r['mes'] ?? '', $r['hora'] ?? '', $r['dia_semana'] ?? '',
            $r['id_edificio'] ?? '', $r['edificio'] ?? '', $r['tipo_edificio'] ?? '', $r['id_ambiente'] ?? '', $r['ambiente'] ?? '', $r['tipo_ambiente'] ?? '',
            $r['capacidad'] ?? '', $r['ocupacion'] ?? '', $r['temperatura'] ?? '', $r['demanda_pico_kw'] ?? '', $r['factor_potencia'] ?? '',
            $r['consumo_kwh'] ?? '', $r['eficiencia'] ?? '', $r['riesgo_sobreconsumo'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'message' => 'Formato no soportado.']);
