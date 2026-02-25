<?php

namespace App\Controllers;

use App\Models\User;

class ProfileController {

    public function index() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header('Location: /?action=login');
            exit;
        }

        $user = User::getById($userId);
        $stats = User::getStats($userId);

        if (!$user) {
            header('Location: /?action=logout');
            exit;
        }

        include __DIR__ . '/../Views/profile.php';
    }

    public function updatePassword() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        // Validações
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
            return;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres']);
            return;
        }

        // Atualizar senha
        $result = User::updatePassword($userId, $currentPassword, $newPassword);
        echo json_encode($result);
    }

    public function updateProfile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $username = $data['username'] ?? '';

        // Validações
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Nome de usuário é obrigatório']);
            return;
        }

        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Nome de usuário deve ter no mínimo 3 caracteres']);
            return;
        }

        // Atualizar perfil
        $result = User::updateProfile($userId, ['username' => $username]);
        echo json_encode($result);
    }
}

