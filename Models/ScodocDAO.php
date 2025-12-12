<?php 
require_once __DIR__. "/Etudiant.php";
require_once __DIR__. "/Formation.php";
require_once __DIR__. "/Departement.php";
require_once __DIR__. "/UE.php";
require_once __DIR__. "/RCUE.php"; 
require_once __DIR__. "/Formsemestre.php";
require_once __DIR__. "/Decision.php";


    
class ScodocDAO implements SourceDataDAO { // va se co a scodoc et recup les infos, tous lers etudiants + tous les dep ect en istanciant 
    private string $api_url="https://scodoc.univ-paris13.fr/ScoDoc/api";
    private ?string $token=null;

    private string $username;
    private string $password;

    public function __construct(string $username, string $password) {
        $this->username = $username;
        $this->password = $password;
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
    if ($reponse===false ) {
        curl_close($curl);
        throw new RuntimeException(curl_error($curl));
    }
    else {
  
    curl_close($curl);
    $json=json_decode($reponse,true);
    if (!isset ($json["token"])) {
        throw new RuntimeException("token invalide");}
    else {
    $this->token = $json["token"];
    }
}



    
}

    protected function get(string $url):array{ // la fonction elle appelle l api avec l url souhaite, comme ca on appelle cette methode pour recup info sur par exemple etuidfiant 
        $this->getToken();
        $curl= curl_init();
        curl_setopt_array($curl,[
        CURLOPT_URL => $this->api_url . "/" . $url,
    
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [ "Authorization: Bearer {$this->token}", "Accept: application/json"]
                    ]);

        $reponse=curl_exec($curl) ;
        curl_close($curl);
        $data=json_decode($reponse,true);
        if ($data===null) {
            throw new RuntimeException("JSON invalide ");
                    }
        return $data;
    }







    public function findall_etudiant(): array {
        $etudiants=$this->get("etudiants");
        $instances=[];
        foreach ($etudiants as $donnees_etudiant) {
            $instances[]=new Etudiant($donnees_etudiant);
        }
        return $instances;

}



    public function findall_ue(){
        $ues= $this->get("ues");
        $instances=[];
        foreach ($ues as $donnees_ues) {
            $instances[]=new UE($donnees_ues);
        }
        return $instances;
    }



    public function findall_departement() {
        $departements= $this->get("departements");
        $instances=[];
        foreach ($departements as $donnees_departement) {
            $instances[]=new Departement($donnees_departement);
        }
        return $instances;
    }



    public function findall_formsemestre(){
        $formsemestres= $this->get("formsemestres");
        $instances=[];
        foreach ($formsemestres as $donnees_formsmestre) {
            $instances[]=new Formsemestre($donnees_formsmestre);
        }
        return $instances;
    
    }


    public function findall_decision() { // pas terminer 
        $decisions= $this->get("decisions");
        $instances=[];
        foreach ($decisions as $donnees_decision) {
            $instances[]=new Decision($donnees_decision);
        }
        return $instances;  


        

    }


    public function findformation_by_accronyme(string $accronyme) {
        $donnnes_formation= $this->get("formations/$accronyme");
        return new Formation($donnnes_formation);
    }


    public function finddepartement_by_accronyme(string $accronyme) {
        $donnees_departement =$this->get("departements/$accronyme");
        return new Departement ($donnees_departement);
        
    }

    public function findformsemestre_by_id(string $id) {   
        $donnees_formsemestre= $this->get("formsemestres/$id");
        return new Formsemestre($donnees_formsemestre);
        
         
    }





    }