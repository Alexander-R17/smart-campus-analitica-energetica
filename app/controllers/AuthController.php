<?php
class AuthController {
    public function landing(): void {
        require __DIR__ . '/../views/landing.php';
    }

    public function login(): void {
        $error = $_GET['error'] ?? null;
        require __DIR__ . '/../views/login.php';
    }

    public function authenticate(): void {
        $user = $_POST['usuario'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($user === 'Ingeniero1' && $pass === '12345678') {
            $_SESSION['user'] = $user;
            header('Location: index.php?route=dashboard');
            exit;
        }
        header('Location: index.php?route=login&error=1');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
