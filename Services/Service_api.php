<?php
require_once __DIR__ ."/models/Etudiant.php"; //exemple de model car api renvoie json avec objet comme ca et faut import dao aussi 
require_once __DIR__ ."/models/Ue.php";
require_once __DIR__ ."/models/Annee.php";
require_once __DIR__ ."/models/Rcue.php";



class Service_api {
    private string $api_url ;
    private string $username;
    private string $password;

    private ?string $token ;

    public function __construct(string $api_url, string $username, string $password, string $token) {
        $this->api_url = "https://ton-scodoc.fr/ScoDoc/api";
        $this->username = "jsp ";
        $this->password = "jsp";
        $this->token = null;

}

    private function authentification () {// on s'authentifie a l api scodoc avec la methode curl, en faisant une requete pour le token avec mdp et login 

        if ($this->token !== null) {// on regarde si on a deja un token 
            return; 
    } 

    $curl= curl_init();
    //obtenir un token si on en a pas 
    curl_setopt_array($curl,[
    CURLOPT_URL => $this->api_url . '/tokens',
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(["username"=>$this->username, "password"=>$this->password])
    ]);
    
    
    
    
  $reponse=curl_exec($curl) ;
  
  curl_close($curl);
  $json=json_decode($reponse,true);
  $this->token = $json["token"];



    
}
private function call(string $url):array{ // la fonction elle appelle l api avec l url souhaite, comme ca on appelle cette methode pour recup info sur par exemple etuidfiant 
    $this->authentification();
    $curl= curl_init();
    curl_setopt_array($curl,[
    CURLOPT_URL => $this->api_url . $url,
    
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [ "Authorization: Bearer {$this->token}", "Accept: application/json"]
]);

    $reponse=curl_exec($curl) ;\
    curl_close($curl);
    return json_decode($reponse,true);

}


public function getEtudiants (){
    $json=$this->call("/etudiants");
    $etudiants=[];
    foreach($json as $etudiant){
        $etudiants[]=$this->JsonToEtudiant($etudiant);
}
    return $etudiants;
}
public function JsonToEtudiant($json){
    $e = new Etudiant();
    $e->setEtudid($json['etudid'] ?? null);
    $e->setCodeNip($json['code_nip'] ?? null);
    $e->setCodeIne($json['code_ine'] ?? null);
    $e->setIsApc($json['is_apc'] ?? null);
    $e->setEtat($json['etat'] ?? null);
    $e->setNbCompetences($json['nb_competences'] ?? null);

    // apres il faut faire les objet rcues, ues et annees 
}
}
?>