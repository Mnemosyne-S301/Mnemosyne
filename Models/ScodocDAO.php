<?php 
require_once __DIR__. "/Etudiant.php";
require_once __DIR__. "/Formation.php";
require_once __DIR__. "/Departement.php";
require_once __DIR__. "/UE.php";
require_once __DIR__. "/RCUE.php"; 
require_once __DIR__. "/Formsemestre.php";
require_once __DIR__. "/Decision.php";


    
class ScodocDAO implements SourceDataDAO { // va se co a scodoc et recup les infos, tous lers etudiants + tous les dep ect en istanciant 
    private string $api_url="https://scodoc.iutv.univ-paris13.fr/ScoDoc/api";
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
        throw new RuntimeException("token invalide");
                                }
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





/*====================================
    ETUDIANTS
=====================================*/

    public function findall_etudiant(): array {
        $etudiants=$this->get("etudiants/courants");
        $instances=[];
        foreach ($etudiants as $donnees_etudiant) {
            $instances[]=new Etudiant($donnees_etudiant);
        }
        return $instances;

}
    public function findEtudiantsByDepartement(string $dept){
        $etudiants = $this->get("departement/$dept/etudiants");
        $instances=[];
        foreach ($etudiants as $donnees_etudiant) {
            $instances[]=new Etudiant($donnees_etudiant);
        }
        return $instances;

    }

/*====================================
            DEPARTEMENTS
=====================================*/


    public function findall_departement() {
        $departements= $this->get("departements");
        $instances=[];
        foreach ($departements as $donnees_departement) {
            $instances[]=new Departement($donnees_departement);
        }
        return $instances;
    }

        public function finddepartement_by_accronyme(string $accronyme) {
        $donnees_departement =$this->get("departement/$accronyme");
        return new Departement ($donnees_departement);
        
    }

/*====================================
            FORMATIONS
=====================================*/
  public function findall_formation () {
            $formations= $this->get("formations");
        $instances=[];
        foreach ($formations as $donnees_formation) {
            $instances[]=new Formation($donnees_formation);
        }
        return $instances;
    }

  public function findformation_by_id(string $id) {
        $donnnes_formation= $this->get("formation/$id");
        return new Formation($donnnes_formation);
    }

/*====================================
            FORMSEMESTRES
=====================================*/

    public function findall_formsemestre(){
        $formsemestres= $this->get("formsemestres/query");
        $instances=[];
        foreach ($formsemestres as $donnees_formsmestre) {
            $instances[]=new Formsemestre($donnees_formsmestre);
        }
        return $instances;
    
    }


        public function findformsemestre_by_id(string $id) {   
        $donnees_formsemestre= $this->get("formsemestre/$id/with_description");
        return new Formsemestre($donnees_formsemestre);
        
         
    }

        public function findFormsemestresByFormation(string $id){
        $formsemestres = $this->get("formsemestres/query?formation_id=$id");
        $instances=[];
        foreach ($formsemestres as $donnees_formsemestres) {
            $instances[]=new Formsemestre($donnees_formsemestres);
        }
        return $instances;
    }



/*====================================
            UES
=====================================*/
    public function findall_ue(){ // y'a pas de route api qui renvoie tous les ues donc alternative mais a revoir 
        $formations = $this->get("formations");
        $instances=[];

        foreach ($formations as $formation) {
            $export=$this->get("formation/" . $formation["formation_id"] . "/export");
            if (isset($export["ues"])) {
                foreach ($export["ues"] as $ue) {
                    $instances[]=new UE($ue);
            
        }
        
    }
   }
   return $instances;
}



    public function findUEByCode(string $code){ // y'a pas de route api qui renvoie tous les ues donc alternative mais a revoir 
        $formations = $this->get("formations");
        
        foreach ($formations as $formation) {
            $export=$this->get("formation/" . $formation["formation_id"] . "/export");
            if (isset($export["ues"])) {
                foreach ($export["ues"] as $ue) {
                    if ($ue["code"] == $code) {
                        return new UE($ue);
                    }
                }
            }
        }
    return null;
    }
            




/*====================================
            DECISIONS
=====================================*/

  

    public function findall_decision() { // onj doit passer par toute les formations d abbord 
        
        $formsemestres= $this->get("formsemestres/query");
        $instances=[];

        foreach ($formsemestres as $formsemestre) {
            $decisions= $this->get("formsemestre/" . $formsemestre["formsemestre_id"] . "/decisions_jury");
            foreach ($decisions as $donnees_decision) {
                $instances[]=new Decision($donnees_decision);
        }
         
    }
    return $instances; 
    }



    public function findDecisionsByFormsemestre(string $id){
        $decisions = $this->get("formsemestre/$id/decisions_jury");
        $instances=[];
        foreach ($decisions as $donnees_decision) {
            $instances[]=new Decision($donnees_decision);
        }
        return $instances;
    }



/*====================================
            RCUES   
=====================================*/


public function findall_rcue(): array
{
    $formations = $this->get("formations");
    $instances = [];

    foreach ($formations as $formation) {

        // On filtre les BUT
        if (
            !isset($formation["type_titre"]) ||
            strtoupper($formation["type_titre"]) !== "BUT"
        ) {
            continue;
        }

        $formation_id = $formation["formation_id"];

        // Récupération référentiel
        $referentiel = $this->get("formation/" . $formation_id . "/referentiel_competences");

        if (!isset($referentiel["competences"])) {
            continue;
        }

        // Parcours compétences BUT
        foreach ($referentiel["competences"] as $competence) {

            $data = [
                "nomCompetence"     => $competence["titre"] ?? "",
                "niveau"            => count($competence["composantes"] ?? []),
                "anneeformation_id" => $competence["ordre"] ?? null,
                "formation_id"      => $formation_id
            ];

            $instances[] = new RCUE($data);
        }
    }

    return $instances;
}
    






    }