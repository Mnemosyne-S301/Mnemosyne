<?php

class Controller_accueil extends Controller {

    /* ajouter les autres méthodes ici sous la forme action_quelquechose */
    
    public function action_default() {
    /*  $nomdesformation il faudra le récupérer depuis la BDD plus tard */
    /*  $annéformation il faudra recuperer depuis la BDD les années ou les formation ont eu lieu */
        $this->render('accueil');
    }

}
?>