<?php

require_once ("Services/Service_scodoc.php");

class Controller_accueil extends Controller {
    private Service_scodoc $service_scodoc;


    /* ajouter les autres méthodes ici sous la forme action_quelquechose */
    
    public function action_default() {
        session_start();
        return $this->render('accueil');
    }
    
}
?>