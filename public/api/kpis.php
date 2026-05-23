<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'No autorizado.']);
    exit;
}
require_once __DIR__ . '/../../app/models/SmartCampusModel.php';
$model = new SmartCampusModel();
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
echo json_encode(['ok' => true, 'data' => $model->getKpis($filters)], JSON_UNESCAPED_UNICODE);
