<?php

namespace App\Models;

use PDO;
use Exception;
use App\Config\Database;

class User {
    private static function getConnection() {
        return (new Database())->connect();
    }

    public static function authenticate($username, $password) {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND status = 'active'");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public static function getById($id) {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function isLocked($ip) {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT locked_until FROM login_attempts WHERE ip_address = :ip");
        $stmt->execute([':ip' => $ip]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($attempt && $attempt['locked_until']) {
            $lockedUntil = strtotime($attempt['locked_until']);
            if ($lockedUntil > time()) {
                return $lockedUntil;
            }
        }
        return false;
    }

    public static function recordAttempt($ip) {
        $db = self::getConnection();
        $stmt = $db->prepare("SELECT * FROM login_attempts WHERE ip_address = :ip");
        $stmt->execute([':ip' => $ip]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($attempt) {
            $newAttempts = $attempt['attempts'] + 1;
            $lockedUntil = null;
            if ($newAttempts >= 3) {
                $lockedUntil = date('Y-m-d H:i:s', strtotime('+24 hours'));
            }
            $stmt = $db->prepare("UPDATE login_attempts SET attempts = :attempts, locked_until = :locked_until WHERE ip_address = :ip");
            $stmt->execute([
                ':attempts' => $newAttempts,
                ':locked_until' => $lockedUntil,
                ':ip' => $ip
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (:ip, 1)");
            $stmt->execute([':ip' => $ip]);
        }
    }

    public static function resetAttempts($ip) {
        $db = self::getConnection();
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
        $stmt->execute([':ip' => $ip]);
    }

    public static function updatePassword($userId, $currentPassword, $newPassword) {
        $db = self::getConnection();

        // Verificar senha atual
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Senha atual incorreta'];
        }

        // Validar nova senha
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres'];
        }

        // Atualizar senha
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);

        if ($success) {
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar senha'];
    }

    public static function updateProfile($userId, $data) {
        $db = self::getConnection();

        // Por enquanto, apenas username pode ser atualizado
        // Verificar se o username já existe (se foi alterado)
        if (isset($data['username'])) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->execute([
                ':username' => $data['username'],
                ':id' => $userId
            ]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Nome de usuário já está em uso'];
            }

            // Atualizar username
            $stmt = $db->prepare("UPDATE users SET username = :username, updated_at = NOW() WHERE id = :id");
            $success = $stmt->execute([
                ':username' => $data['username'],
                ':id' => $userId
            ]);

            if ($success) {
                $_SESSION['username'] = $data['username'];
                return ['success' => true, 'message' => 'Perfil atualizado com sucesso'];
            }
        }

        return ['success' => false, 'message' => 'Nenhuma alteração realizada'];
    }

    public static function getStats($userId) {
        $db = self::getConnection();

        // Buscar estatísticas do usuário
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_operations,
                COALESCE(SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END), 0) as completed,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active,
                COALESCE(SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END), 0) as expired,
                COALESCE(SUM(max_profit), 0) as total_profit
            FROM operations 
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
