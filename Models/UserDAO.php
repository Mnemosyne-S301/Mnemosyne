<?php
require_once __DIR__ . "/User.php";
require_once __DIR__ . "/DB.php";


/**
 * Class UserDAO
 *
 * DAO pour la gestion des comptes administrateurs.
 */
class UserDAO {

        private PDO $pdo;

        public function __construct() {
                $this->pdo = DB::get();
        }

        public function findAll() : array {
                $pdo = DB::get();
                $requete = $pdo->prepare("SELECT user_id AS id, username, role FROM Users");
                $requete->execute();
                $data = $requete->fetchAll(PDO::FETCH_ASSOC);
                $users = [];
                if ($data) {
                        foreach ($data as $user_row) {
                                $users[] = new User($user_row);
                        }
                }
                return $users;
        }

        public function findbyname(string $username) : ?User {
                $pdo = DB::get();
                $requete = $pdo->prepare("SELECT user_id AS id, username, role FROM Users WHERE username=:username");
                $requete->bindParam(":username", $username, PDO::PARAM_STR);
                $requete->execute();
                $data = $requete->fetch(PDO::FETCH_ASSOC);

                if ($data) {
                        return new User($data);
                } else {
                        return null;
                }
        }

        public function findbyrole(string $role) : array {
                $pdo = DB::get();
                $requete = $pdo->prepare("SELECT user_id AS id, username, role FROM Users WHERE role=:role");
                $requete->bindParam(":role", $role, PDO::PARAM_STR);
                $requete->execute();
                $data = $requete->fetchAll(PDO::FETCH_ASSOC);

                $users = [];
                foreach ($data as $row) {
                        $users[] = new User($row);
                }
                return $users;
        }

        public function createUser(string $username, string $password, string $role) : bool {
                $pdo = DB::get();
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $requete = $pdo->prepare("INSERT INTO Users (username, password_hash, role) VALUES (:username, :password, :role)");
                $requete->bindParam(":username", $username, PDO::PARAM_STR);
                $requete->bindParam(":password", $hash, PDO::PARAM_STR);
                $requete->bindParam(":role", $role, PDO::PARAM_STR);
                return $requete->execute();
        }

        public function deleteById(int $id) : bool {
                $pdo = DB::get();
                $requete = $pdo->prepare("DELETE FROM Users WHERE user_id = :id");
                $requete->bindParam(":id", $id, PDO::PARAM_INT);
                return $requete->execute();
        }

        public function authenticate(string $username, string $password): ?User {
                $pdo = DB::get();
                $stmt = $pdo->prepare("SELECT user_id AS id, username, role, password_hash FROM Users WHERE username = :u");
                $stmt->execute([":u" => $username]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) return null;
                if (!password_verify($password, $row["password_hash"])) return null;

                unset($row["password_hash"]);
                return new User($row);
        }

}

?>
