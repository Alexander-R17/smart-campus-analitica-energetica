<?php
session_start();
$route = $_GET['route'] ?? 'landing';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';

$auth = new AuthController();
$dashboard = new DashboardController();

switch ($route) {
    case 'login':
        $auth->login();
        break;
    case 'authenticate':
        $auth->authenticate();
        break;
    case 'logout':
        $auth->logout();
        break;
    case 'dashboard':
        $dashboard->index();
        break;
    case 'graphs':
        $dashboard->graphs();
        break;
    default:
        $auth->landing();
        break;
}
