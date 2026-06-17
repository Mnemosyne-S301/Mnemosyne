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
    public function requireAdmin(): void
    {
        if (!$this->service_auth->isAdmin()) {
            http_response_code(403);
            exit("Accès interdit : vous devez être ADMIN");
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


    /*
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

        if (isset($_SESSION['id']) && $id > 0 && $id !== (int)$_SESSION['id']){
            $this->service_admin->deleteAdmin($id);
        }
    }

    header("Location: index.php?controller=admin&action=default");
    exit;
}

public function action_synchroniser()
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Méthode non autorisée.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $scriptPath = '/var/www/html/scripts/ajout-donnees.py';
    $pythonPath = '/var/www/html/venv/bin/python';

    if ($scriptPath === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Script Python introuvable.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($pythonPath === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Environnement Python introuvable.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $command =
        'DB_HOST=mnemosyne-mnemosyne-mysql-1 ' .
        'DB_USER=phpserv ' .
        'DB_PASSWORD=phpserv ' .
        'DB_NAME=scolarite ' .
        escapeshellcmd($pythonPath) . ' ' .
        escapeshellarg($scriptPath) . ' 2>&1';

    $output = trim(shell_exec($command) ?? '');
    $json = json_decode($output, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Réponse du script Python invalide.',
            'json_error' => json_last_error_msg(),
            'raw_output' => $output
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}
    
    

}
?>
