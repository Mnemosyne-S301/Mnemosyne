<?php

class Controller_admin extends Controller {

    /* ajouter les autres méthodes ici sous la forme action_quelquechose */

    
    public function action_default() {
        return $this->render("view_admin.php",);
        /* Méthode abstraite à reimplementer sinon ça marchera pas */
    }

}
?>