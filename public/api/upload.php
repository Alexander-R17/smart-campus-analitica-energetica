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

if (empty($_FILES['files'])) {
    echo json_encode(['ok' => false, 'message' => 'No se recibieron archivos.']);
    exit;
}

$files = $_FILES['files'];
$totalInserted = 0;
$details = [];
for ($i = 0; $i < count($files['name']); $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
        $details[] = ['file' => $files['name'][$i], 'ok' => false, 'message' => 'Error de carga.'];
        continue;
    }
    $tmp = $files['tmp_name'][$i];
    $result = $model->importCsv($tmp);
    $result['file'] = $files['name'][$i];
    $totalInserted += (int)($result['inserted'] ?? 0);
    $details[] = $result;
}

echo json_encode(['ok' => true, 'inserted' => $totalInserted, 'details' => $details]);
