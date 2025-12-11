<?php 
require_once __DIR__ . "DB.php";

abstract class DAO {
private PDO $pdo;
private string $token ;
private string $api_url="https://ton-scodoc.fr/ScoDoc/api";


    protected function __getdbconnction(): PDO {
        $this->pdo = DB ::getConnectionDB();
        return $this->pdo;  

    }

    protected function getToken () {// on s'authentifie a l api scodoc avec la methode curl, en faisant une requete pour le token avec mdp et login 

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
    protected function get(string $url):array{ // la fonction elle appelle l api avec l url souhaite, comme ca on appelle cette methode pour recup info sur par exemple etuidfiant 
    $this->getToken();
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
   abstract public function findall();


}


?>