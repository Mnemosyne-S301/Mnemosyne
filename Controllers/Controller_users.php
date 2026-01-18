<?php

class Controller_users extends Controller {

    public function __construct() {
        parent::__construct();
    }
    
    public function action_default() {
        // Rediriger vers l'accueil par défaut
        header('Location: index.php?controller=accueil');
        exit;
    }

}
?>