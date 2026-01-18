<?php
require_once  __DIR__ ."/../Models/ScodocDAO.php";
require_once  __DIR__ ."/../Models/ScolariteDAO.php";
require_once __DIR__ ."/../Models/DB.php";

require_once __DIR__. "/../Models/Etudiant.php";
require_once __DIR__. "/../Models/Formation.php";
require_once __DIR__. "/../Models/Departement.php";
require_once __DIR__. "/../Models/UE.php";
require_once __DIR__. "/../Models/RCUE.php"; 
require_once __DIR__. "/../Models/Formsemestre.php";
require_once __DIR__. "/../Models/Decision.php";


// il manque RCUE, effectuer ue et effectuer rcue, recup donnee par annee

class Service_syn {
    private ScodocDAO $SourceDAO;
    private ScolariteDAO $DestDAO;

    private  PDO $PDO;

    public function __construct(String $username , String $password) {
        $this->SourceDAO = new ScodocDAO($username , $password);
        $this->DestDAO = ScolariteDAO::getModel();
        $this->PDO = DB::get(); 
    }

    public function synchoniser(){
        try {
            $this->PDO->beginTransaction();
            $this->DestDAO->resetDatabase();

            $departements = $this->SourceDAO->findall_departement();
            $etudiants = $this->SourceDAO->findall_etudiant();
            $formations = $this->SourceDAO->findall_formation();
            $formsemestres= $this->SourceDAO->findall_formsemestre();



            $ues= $this->SourceDAO->findall_ue();
            $rcues= $this->SourceDAO->findall_rcue();
            $decisions= $this->SourceDAO->findall_decision();


            $this->DestDAO->addDepartement($this->objsToRows($departements));
            $this->DestDAO->addFormation($this->objsToRows($formations));
            $this->DestDAO->addFormSemestre($this->objsToRows($formsemestres));
            $this->DestDAO->addEtudiant($this->objsToRows($etudiants));



            $codes = $this->defaultCodes();
            $this->DestDAO->addCodeAnnee($codes);
            $this->DestDAO->addCodeUE($codes);
            $this->DestDAO->addCodeRCUE($codes);

            $butFormationIds = $this->SourceDAO->findBUTFormationIds();


            $this->PDO->commit();
            echo "Transaction faite !";

        }
        catch (Exception $e){
            $this->PDO->rollback();
            echo "Erreur durant transaction !" . $e->getMessage();
        
        }




    }

  


  private function objsToRows(array $objects): array {
        $rows = [];
        foreach ($objects as $o) $rows[] = $o->toDict();
        return $rows;
    }

    private function defaultCodes(): array {
        return [
            ['code'=>'ADM', 'signification'=>'Admis'],
            ['code'=>'AJ',  'signification'=>'Ajourné'],
            ['code'=>'ADMC','signification'=>'Admis par compensation'],
            ['code'=>'DEF', 'signification'=>'Défaillant'],
            ['code'=>'ABS', 'signification'=>'Absent'],
        ];
    }


}
