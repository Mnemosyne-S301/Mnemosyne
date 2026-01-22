<?php
require_once __DIR__ . "/../Models/UserDAO.php";
require_once __DIR__ . "/../Models/DB.php";

class Service_auth {
    private UserDAO $dao;

    public function __construct() {
        $this->dao = new UserDAO();
    }

    public function login($username, $password)  {
        $user = $this->dao->findbyname($username);
        if ($user == null) {
            return false;
        }

        if (password_verify($password, $user->getMdp())) {
            return $user;
        }

        return false;
    }

    public function isLogged() : bool {
        if (isset($_SESSION["logged"]) && $_SESSION["logged"] == true) {
            return true;
        }
        return false;
    }

    public function logout() : void {
        session_destroy();
    }

    public function hashMdp(string $password) : string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function createUser(string $username, string $password)  {
        $password = password_hash($password, PASSWORD_BCRYPT);
        if ($this->dao->findbyname($username)) {
            echo "Cet utiulisateur existe deja ";
            return false;
        }

        return $this->dao->createUser($username, $password);
    }

    public function isAdmin(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->isLogged()) {
            return false;
        }

        if (!isset($_SESSION["role"])) {
            return false;
        }

        return strtolower((string)$_SESSION["role"]) === "admin";
    }
}
?>