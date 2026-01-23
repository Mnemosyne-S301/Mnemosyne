<?php
require_once __DIR__ . "/../Services/Service_auth.php";
require_once __DIR__ . "/../Services/Service_admin.php";


/**
 * Class Controller_admin
 *
 * Contrôleur MVC dédié à l’espace administrateur.
 *
 * Responsabilités :
 * - Protéger l’accès aux fonctionnalités admin (contrôle des droits)
 * - Afficher la page admin (liste des administrateurs)
 * - Ajouter un administrateur (via POST)
 * - Supprimer un administrateur (via POST)
 *
 * Dépendances :
 * - Service_auth : vérifie l’état de connexion et le rôle admin via la session
 * - Service_admin : logique métier pour gérer les comptes administrateurs
 */
class Controller_admin extends Controller {

    /**
     * Service d’authentification (session + rôle).
     *
     * @var Service_auth
     */
    private Service_auth $service_auth;

    /**
     * Service métier pour la gestion des administrateurs.
     *
     * @var Service_admin
     */
    private Service_admin $service_admin;

    /**
     * Constructeur du contrôleur admin.
     *
     * Injection des services nécessaires.
     *
     * @param Service_admin $service_admin Service de gestion des administrateurs
     * @param Service_auth $service_auth Service d’authentification et contrôle d’accès
     */
    public function __construct(){
        $this->service_auth = new Service_auth;
        $this->service_admin = new Service_admin;
        parent::__construct();
    }

    /**
     * Vérifie que l’utilisateur courant est administrateur.
     *
     * Fonctionnement :
     * - Démarre la session si besoin
     * - Vérifie le rôle admin via Service_auth
     * - Si non admin : renvoie une erreur HTTP 403 et stoppe le script
     *
     * @return void
     */
    public function requireAdmin(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->service_auth->isAdmin()){
            http_response_code(403);
            exit("Acces interdit (vous devez etre ADMIN)");
        }
    }

    /**
     * Action par défaut du contrôleur admin.
     *
     * - Vérifie l’accès admin
     * - Récupère la liste des administrateurs
     * - Rend la vue "admin" en lui passant les données
     *
     * @return mixed Résultat du rendu de la vue (selon l’implémentation de render)
     */
    public function action_default() {
        $this->requireAdmin();

        $admins = $this->service_admin->listAdmin();

        return $this->render("admin", ['admins' => $admins]);
        /* Méthode abstraite à reimplementer sinon ça marchera pas */
    }

    /**
     * Ajoute un administrateur.
     *
     * Déclenchée via une requête POST.
     *
     * Étapes :
     * - Vérifie l’accès admin
     * - Vérifie la méthode HTTP (POST)
     * - Récupère username/password depuis $_POST
     * - Appelle le service pour créer l’admin
     * - Redirige vers l’action default (liste des admins)
     *
     * @return void
     */
    public function action_addAdmin(){
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === "POST"){
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if ($username != "" && $password != ""){
                $this->service_admin->addAdmin($username, $password);
            }
        }

        header("Location: index.php?controller=admin&action=default");
        exit;
    }

    /**
     * Supprime un administrateur.
     *
     * Déclenchée via une requête POST.
     *
     * Étapes :
     * - Vérifie l’accès admin
     * - Vérifie la méthode HTTP (POST)
     * - Récupère l'id depuis $_POST et le convertit en int
     * - Appelle le service pour supprimer l’admin
     * - Redirige vers l’action default (liste des admins)
     *
     * @return void
     */
    public function action_delAdmin(){
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === "POST"){
            $id = (int)$_POST['id'];

            if ($id > 0){
                $this->service_admin->deleteAdmin($id);
            }
        }

        header("Location: index.php?controller=admin&action=default");
        exit;
    }

}
?>
