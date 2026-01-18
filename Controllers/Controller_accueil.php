<?php

class Controller_accueil extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function action_default() {
        $this->render('accueil');
    }

}
?>