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
}
