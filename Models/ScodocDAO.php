<?php 
require_once __DIR__. "/Etudiant.php";
require_once __DIR__. "/Formation.php";
require_once __DIR__. "/Departement.php";
require_once __DIR__. "/UE.php";
require_once __DIR__. "/RCUE.php"; 
require_once __DIR__. "/Formsemestre.php";
require_once __DIR__. "/Decision.php";


/**
 * Le DAO permettant de recuperer les données depuis l'API ScoDoc
 * @package DAO
 */    
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
            




  

    public function findall_decision() { // on doit passer par toute les formations d abbord 
        
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



public function findReferentielCompetencesByFormation(string $formationId): array {
    return $this->get("formation/$formationId/referentiel_competences");
}


public function findall_rcue(): array {
    // RCUEs are derived from formations' referentiel de compétences
    $formations = $this->get("formations");
    $instances = [];
    $seen = []; // To avoid duplicates
    
    foreach ($formations as $formation) {
        $formationId = $formation['formation_id'] ?? $formation['id'] ?? null;
        if ($formationId === null) continue;
        
        try {
            $referentiel = $this->get("formation/$formationId/referentiel_competences");
            
            if (isset($referentiel['competences'])) {
                foreach ($referentiel['competences'] as $competence) {
                    $nomCompetence = $competence['titre'] ?? $competence['nom'] ?? '';
                    
                    // Get niveaux for this competence
                    $niveaux = $competence['niveaux'] ?? [];
                    foreach ($niveaux as $niveau) {
                        $niveauNum = $niveau['niveau'] ?? $niveau['ordre'] ?? null;
                        $anneeFormationId = $niveau['annee_formation_id'] ?? $formationId;
                        
                        $key = "$nomCompetence-$niveauNum-$anneeFormationId";
                        if (!isset($seen[$key])) {
                            $seen[$key] = true;
                            $instances[] = new RCUE([
                                'nomCompetence' => $nomCompetence,
                                'niveau' => $niveauNum,
                                'anneeformation_id' => $anneeFormationId
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Some formations may not have referentiel_competences, skip them
            continue;
        }
    }
    
    return $instances;
}


    public function findFormsemestresByAnnee(int $annee): array {
    return $this->get("formsemestres/query?annee_scolaire=" . $annee);
}


public function findBUTFormationIds(): array {
    $formations = $this->get("formations");
    $ids = [];

    foreach ($formations as $f) {
        $isBUT = (isset($f['type_titre']) && $f['type_titre'] === 'BUT')
              || (isset($f['titre']) && stripos($f['titre'], 'BUT') === 0);

        if (!$isBUT) continue;

        $id = $f['formation_id'] ?? $f['id'] ?? null;
        if ($id !== null) $ids[] = (int)$id;
    }
    return $ids;
}

public function findDecisionsBUTByAnnee(int $annee, array $butFormationIds): array {
    $formsemestres = $this->findFormsemestresByAnnee($annee);
    $all = [];

    foreach ($formsemestres as $fs) {
        $formationId = $fs['formation_id']
            ?? ($fs['formation']['formation_id'] ?? null)
            ?? null;

        if ($formationId === null) continue;
        if (!in_array($formationId, $butFormationIds, true)) continue;

        $formsemestreId = $fs['id'] ?? $fs['formsemestre_id'] ?? null;
        if ($formsemestreId === null) continue;

        $decisions = $this->get("formsemestre/$formsemestreId/decisions_jury");

        // IMPORTANT : on garde aussi contexte (annee + formsemestre_id + formation_id)
        $all[] = [
            "annee_scolaire"   => $annee,
            "formsemestre_id"  => (int)$formsemestreId,
            "formation_id"     => (int)$formationId,
            "decisions"        => $decisions,
        ];
    }

    return $all;
}




    }