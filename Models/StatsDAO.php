<?php

class StatsDAO{
    private $conn;
    private static $instance = null; 

    private function __construct()
    {
        $this->conn = new PDO('mysql:host=localhost;dbname=Stats', 'root', '1234');
        // l'utilisateur ici est phpserv avec comme mot de passe mdptest . Pensez enventuellement à changer ça selon votre configuration.
    }

    public static function getModel()
    {
        if (self::$instance === null)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function getNbEleveParFormation($formation){
        

    }

}