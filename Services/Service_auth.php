<?php
require_once __DIR__ . "/../Models/UserDAO.php";
<<<<<<< HEAD
require_once __DIR__ . "/../Models/DB.php";
=======
>>>>>>> logAdmin

/**
 * Class Service_auth
 *
 * Service d’authentification de l’application.
 *
 * Cette classe centralise toute la logique liée :
 * - à la connexion (login)
 * - à la vérification de l’état de connexion
 * - aux droits administrateur
 * - à la déconnexion
 *
 * Elle agit comme une couche métier entre le contrôleur
 * et le UserDAO.
 */
class Service_auth {
<<<<<<< HEAD
    private UserDAO $dao;
=======
>>>>>>> logAdmin

    /**
     * DAO utilisé pour l'accès aux données utilisateurs / administrateurs.
     *
     * @var UserDAO
     */
    private UserDAO $dao;

    /**
     * Constructeur du service d'authentification.
     *
     * Initialise l'accès aux données via UserDAO.
     */
    public function __construct() {
        $this->dao = new UserDAO();
    }

<<<<<<< HEAD
    public function login($username, $password)  {
        $user = $this->dao->findbyname($username);
        if ($user == null) {
=======
    /**
     * Authentifie un utilisateur (administrateur).
     *
     * Étapes :
     * - Vérifie les identifiants via le DAO
     * - Retourne l’objet User si l’authentification réussit
     * - Retourne false sinon
     *
     * @param string $username Nom de connexion
     * @param string $password Mot de passe en clair
     * @return User|false Objet User si succès, false sinon
     */
    public function login($username, $password) {
        $user = $this->dao->authenticate($username, $password);

        if ($user) {
            return $user;
        } else {
>>>>>>> logAdmin
            return false;
        }

        if (password_verify($password, $user->getMdp())) {
            return $user;
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur est actuellement connecté.
     *
     * La connexion est validée via la variable de session `$_SESSION["logged"]`.
     *
     * @return bool True si connecté, false sinon
     */
    public function isLogged() : bool {
<<<<<<< HEAD
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
=======
        return isset($_SESSION["logged"]) && $_SESSION["logged"] === true;
    }

    /**
     * Déconnecte l’utilisateur courant.
     *
     * Détruit complètement la session PHP.
     *
     * @return void
     */
    public function logout() {
        session_destroy();
    }

    /**
     * Vérifie si l’utilisateur connecté possède le rôle administrateur.
     *
     * Conditions :
     * - l’utilisateur est connecté
     * - un rôle est défini en session
     * - le rôle est égal à "admin"
     *
     * @return bool True si administrateur, false sinon
     */
    public function isAdmin() {
        return $this->isLogged()
            && isset($_SESSION['role'])
            && $_SESSION["role"] === "admin";
    }
}

?>
>>>>>>> logAdmin
