<?php
require_once __DIR__ . '/../Models/StatsDAO.php';

/**
 * @package Service
 */
class Service_scodoc {
    /**
         * @var StatsDAO $dao Instance du Data Access Object pour interagir avec la BDD.
         */
        private StatsDAO $dao;

        /**
         * Constructeur.
         * Initialise le Service en récupérant l'unique instance du StatsDAO (Singleton).
         */
        public function __construct(){
            $this->dao = StatsDAO::getModel();
        }

        public function getAllFormationAccronyme(){
            return $this->dao->getallFormationByAccronyme();

        }
           
        
}
?>