<?php
include_once __DIR__ . "/../Services/Service_Stats.php";
class Controller_api  {
    private Service_stats $service;

    public function __construct() {
        $this->service=new Service_stats();

    }

    public function recupererDonneesSankey(){
          
        if (!isset($_GET['anneeDepart']) || trim($_GET['anneeDepart']) === '') {
        throw new InvalidArgumentException("Paramètre 'anneeDepart' obligatoire.");
    }
    if (!isset($_GET['formation']) || trim($_GET['formation']) === '') {
        throw new InvalidArgumentException("Paramètre 'formation' obligatoire.");
    
    }
    $annee = trim((string)$_GET['anneeDepart']);// si annee est en mode 2020-2021
    $annee  = (int)substr($annee, 0, 4);
   
    $formation=trim($_GET['formation']);

    return $this->service->getSankeyCohorteDepuisAnnee($annee,$formation);
        
    }
}

header('Content-Type: application/json; charset=utf-8');

try {
    $controller = new Controller_api();
    $donnees = $controller->recupererDonneesSankey();

    http_response_code(200);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur'], JSON_UNESCAPED_UNICODE);
}



