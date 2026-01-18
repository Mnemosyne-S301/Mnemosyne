<?php

class Controller_admin extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function action_default() {
        $this->render("admin");
    }

}
?>