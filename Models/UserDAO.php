<?php 
require_once __DIR__ . "/User.php";
require_once __DIR__ . "/DB.php";


/**
 * Class UserDAO
 *
 * Data Access Object (DAO) pour la gestion des comptes administrateurs.
 * Cette classe centralise les accès à la base de données concernant la table `users`.
 *
 * Responsabilités :
 * - Lire les administrateurs (tous / par username / par rôle)
 * - Créer un compte administrateur
 * - Supprimer un compte administrateur
 * - Authentifier un administrateur (vérification mot de passe)
 */
class UserDAO {

    /**
     * Connexion PDO utilisée par le DAO.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructeur de UserDAO.
     *
     * Initialise la connexion à la base de données via DB::get().
     */
    public function __construct() {
        $this->pdo = DB::get();
    }

    /**
     * Récupère tous les comptes (administrateurs) présents dans la table `users`.
     *
     * @return User[] Liste d'objets User (administrateurs). Retourne un tableau vide si aucun résultat.
     */
    public function findAll() : array {
        $pdo = DB::get();
        $requete = $pdo->prepare("SELECT id,username,role  FROM users ");

        $requete->execute();
        $data = $requete->fetchAll();
        $Users = [];

        if ($data) {
            foreach ($data as $user_row) {
                array_push($Users, new User($user_row));
            }
            return $Users;
        } else {
            return [];
        }
    }

    /**
     * Recherche un compte (administrateur) par nom d'utilisateur.
     *
     * @param string $username Nom de connexion recherché.
     * @return User|null Retourne un objet User si trouvé, sinon null.
     */
    public function findbyname(string $username) : ?User {
        $pdo = DB::get();
        $requete = $pdo->prepare("SELECT id,username,role FROM users WHERE username=:username");
        $requete->bindParam(":username", $username, PDO::PARAM_STR);
        $requete->execute();
        $data = $requete->fetch();

        if ($data) {
            return new User($data);
        } else {
            return null;
        }
    }

    /**
     * Recherche des comptes (administrateurs) par rôle.
     *
     * @param string $role Rôle recherché (ex: admin, super-admin).
     * @return User[] Tableau d'objets User correspondant au rôle.
     */
    public function findbyrole(string $role) {
        $pdo = DB::get();
        $requete = $pdo->prepare("SELECT id,username,role FROM users WHERE role=:role");
        $requete->bindParam(":role", $role, PDO::PARAM_STR);
        $requete->execute();
        $data = $requete->fetchAll(PDO::FETCH_ASSOC);

        $users = [];
        foreach ($data as $row) {
            $users[] = new User($row);
        }
        return $users;
    }

    /**
     * Crée un compte (administrateur) en base de données.
     *
     * Le mot de passe est hashé avec PASSWORD_BCRYPT avant insertion.
     *
     * @param string $username Nom de connexion.
     * @param string $password Mot de passe en clair (sera hashé).
     * @param string $role Rôle associé au compte.
     * @return bool True si l'insertion a réussi, sinon false.
     */
    public function createUser(string $username, string $password, string $role)  {
        $pdo = DB::get();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $requete = $pdo->prepare("INSERT INTO users (username,password,role) VALUES (:username, :password,:role)");
        $requete->bindParam(":username", $username, PDO::PARAM_STR);
        $requete->bindParam(":password", $hash, PDO::PARAM_STR);
        $requete->bindParam(":role", $role, PDO::PARAM_STR);

        return $requete->execute();
    }

    /**
     * Supprime un compte (administrateur) à partir de son identifiant.
     *
     * @param int $id Identifiant du compte à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function deleteById(int $id) {
        $pdo = DB::get();
        $requete = $pdo->prepare("DELETE FROM users WHERE id =:id");
        $requete->bindParam(":id", $id, PDO::PARAM_INT);
        return $requete->execute();
    }

    /**
     * Authentifie un administrateur.
     *
     * Fonctionnement :
     * - Récupère l’utilisateur par username
     * - Vérifie le mot de passe avec password_verify()
     * - Retourne un objet User (sans le champ password) si OK, sinon null
     *
     * @param string $username Nom de connexion saisi.
     * @param string $password Mot de passe saisi.
     * @return User|null Objet User si authentification réussie, sinon null.
     */
    public function authenticate(string $username, string $password): ?User {
        $pdo = DB::get();
        $stmt = $pdo->prepare("SELECT id, username, role, password FROM users WHERE username = :u");
        $stmt->execute([":u" => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        if (!password_verify($password, $row["password"])) return null;

        unset($row["password"]);
        return new User($row);
    }



}


?>
