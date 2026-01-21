<?php
require __DIR__ . '/../Services/Service_scodoc.php';

/**
 * @package Controller
 */
class Controller_accueil extends Controller {
    
    private Service_scodoc $Service;

    public function __construct() {
        $this->Service =new Service_scodoc();
        parent::__construct();
    }

  public function action_default() {
    $formations = $this->Service->getAllFormationAccronyme();

    $this->render('accueil', ['formationArray' => $formations]);
}
}
?>