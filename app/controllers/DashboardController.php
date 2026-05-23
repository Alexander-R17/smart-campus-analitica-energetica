<?php
class DashboardController {
    public function index(): void {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?route=login');
            exit;
        }
        require __DIR__ . '/../views/dashboard.php';
    }

    public function graphs(): void {
        if (empty($_SESSION['user'])) {
            header('Location: index.php?route=login');
            exit;
        }
        require __DIR__ . '/../views/graphs.php';
    }
}
