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
$model->resetWarehouse();
echo json_encode(['ok' => true, 'message' => 'Tabla de hechos y ocupación limpiadas. Regrese a Fuentes de Datos.']);
