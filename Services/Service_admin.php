<?php
require_once __DIR__ . "/../Models/UserDAO.php";

/**
 * Class Service_admin
 *
 * Couche service dédiée à la gestion des administrateurs.
 *
 * Cette classe joue le rôle d’intermédiaire entre :
 * - le contrôleur (Controller)
 * - la couche d’accès aux données (UserDAO)
 *
 * Elle encapsule la logique métier liée aux comptes administrateurs
 * (liste, ajout, suppression).
 */
class Service_admin {

    /**
     * DAO utilisé pour accéder aux données des administrateurs.
     *
     * @var UserDAO
     */
    private UserDAO $dao;

    /**
     * Constructeur du service administrateur.
     *
     * Initialise le DAO utilisé pour les opérations sur les comptes admin.
     */
    public function __construct(){
        $this->dao = new UserDAO();
    }

    /**
     * Retourne la liste des administrateurs.
     *
     * Actuellement, la méthode récupère uniquement les utilisateurs
     * ayant le rôle "admin".
     *
     * @return User[] Liste des administrateurs
     */
    public function listAdmin() {
        return $this->dao->findbyrole("admin"); 
        // Remarque : findAll() fonctionnerait aussi car seuls des admins existent
    }

    /**
     * Ajoute un nouvel administrateur.
     *
     * Étapes :
     * - Vérifie si le nom d’utilisateur existe déjà
     * - Hash le mot de passe
     * - Crée le compte avec le rôle "admin"
     *
     * @param string $username Nom de connexion administrateur
     * @param string $password Mot de passe en clair
     * @return bool False si l’utilisateur existe déjà, sinon résultat de l’insertion
     */
    public function addAdmin(string $username, string $password){
        if ($this->dao->findbyname($username)) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        return $this->dao->createUser($username, $hash, "admin");
    }
    
    /**
     * Supprime un administrateur à partir de son identifiant.
     *
     * @param int $id Identifiant de l’administrateur
     * @return bool Résultat de la suppression
     */
    public function deleteAdmin (int $id){
        return $this->dao->deleteById($id);
    }
}
?>

