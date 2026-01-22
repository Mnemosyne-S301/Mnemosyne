<?php
require_once __DIR__ . "/../Models/DB.php";
require_once __DIR__ . "/../Models/User.php";

class Service_admin {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = DB::get();
    }

    public function listAdmin() : array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role = :role ORDER BY username");
        $stmt->execute([':role' => 'admin']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $admins = [];
        foreach ($rows as $row) {
            $admins[] = new User($row);
        }

        return $admins;
    }

    public function addAdmin(string $username, string $password) : bool {
        $username = trim($username);
        if ($username === "" || $password === "") {
            return false;
        }

        $check = $this->pdo->prepare("SELECT 1 FROM users WHERE username = :username LIMIT 1");
        $check->execute([':username' => $username]);
        if ($check->fetchColumn()) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)"
        );

        return $stmt->execute([
            ':username' => $username,
            ':password' => $hash,
            ':role' => 'admin',
        ]);
    }

    public function deleteAdmin(int $id) : bool {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id AND role = :role");
        return $stmt->execute([
            ':id' => $id,
            ':role' => 'admin',
        ]);
    }
}
