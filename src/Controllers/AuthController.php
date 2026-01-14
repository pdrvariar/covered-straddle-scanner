<?php

namespace App\Controllers;

use App\Models\User;

class AuthController {
    public function login() {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $lockedUntil = User::isLocked($ip);

        if ($lockedUntil) {
            $remaining = $lockedUntil - time();
            $hours = ceil($remaining / 3600);
            $error = "Muitas tentativas falhas. Acesso bloqueado por mais aproximadamente $hours horas.";
            include __DIR__ . '/../Views/login.php';
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = User::authenticate($username, $password);

            if ($user) {
                User::resetAttempts($ip);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: /');
                exit;
            } else {
                User::recordAttempt($ip);
                $error = "Usuário ou senha inválidos.";
            }
        }

        include __DIR__ . '/../Views/login.php';
    }

    public function logout() {
        session_destroy();
        header('Location: /?action=login');
        exit;
    }
}
