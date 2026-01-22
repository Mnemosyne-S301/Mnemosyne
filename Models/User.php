<?php

/**
 * Class User
 *
 * Représente un **administrateur** de l'application.
 * Cette classe est utilisée pour manipuler les informations
 * liées à un compte administrateur (authentification, rôle, droits).
 *
 * Les données sont généralement fournies sous forme de tableau
 * (ex : résultat d’une requête SQL avec PDO).
 */
class User {

    /**
     * Identifiant unique de l’administrateur.
     *
     * @var int
     */
    public int $id_user;

    /**
     * Nom de connexion de l’administrateur.
     *
     * @var string
     */
    public string $username;

    /**
     * Rôle de l’administrateur dans le système
     * (ex : admin, super-admin, etc.).
     *
     * @var string
     */
    public string $role;

    /**
     * Constructeur de la classe User (Administrateur).
     *
     * Initialise un objet administrateur à partir
     * d’un tableau de données.
     *
     * Le tableau fourni doit contenir les clés suivantes :
     * - id        : identifiant de l’administrateur
     * - username  : nom de connexion
     * - role      : rôle attribué
     *
     * @param array $data Tableau associatif contenant les données administrateur
     */
    public function __construct(array $data) {

        /**
         * Affectation de l'identifiant administrateur.
         * Conversion explicite en entier pour garantir le type.
         */
        $this->id_user = (int)$data["id"];

        /**
         * Affectation du nom de connexion administrateur.
         */
        $this->username = $data["username"];

        /**
         * Affectation du rôle administrateur.
         */
        $this->role = $data["role"];
    }

    /**
     * Retourne l'identifiant unique de l'administrateur.
     */
    public function getId(): int {
        return $this->id_user;
    }

    /**
     * Retourne le nom de connexion de l'administrateur.
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * Retourne le rôle de l'administrateur.
     */
    public function getRole(): string {
        return $this->role;
    }
}

?>
