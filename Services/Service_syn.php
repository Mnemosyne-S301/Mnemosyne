<?php
require_once  __DIR__ ."/../Models/ScodocDAO.php";
require_once  __DIR__ ."/../Models/ScolariteDAO.php";
require_once __DIR__ ."/../Models/DB.php";

require_once __DIR__. "/Etudiant.php";
require_once __DIR__. "/Formation.php";
require_once __DIR__. "/Departement.php";
require_once __DIR__. "/UE.php";
require_once __DIR__. "/RCUE.php"; 
require_once __DIR__. "/Formsemestre.php";
require_once __DIR__. "/Decision.php";




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


            $this->insertionDepartements($departements);
            $this->insertionEtudiants($etudiants);
            $this->insertionFormations($formations);
            $this->insertionFormsemestres($formsemestres);
            $this->insertionUEs($ues);
            $this->insertionRCUEs($rcues);
            

            $this->PDO->commit();
            echo "Transaction faite !";

        }
        catch (Exception $e){
            $this->PDO->rollback();
            echo "Erreur durant transaction !" . $e->getMessage();
        
        }




    }

    public function insertionDepartements($departements){
        $rows=[];

        foreach($departements as $departement){
            $rows[]= $departement->toDict();
        }

            if(!empty($rows)){
                $this->DestDAO->addDepartement($rows);
            }


    }



    public function insertionEtudiants($etudiants){
        $rows=[];
        foreach($etudiants as $etudiant){
            $rows[]= $etudiant->toDict();
        }
            if(!empty($rows)){
                $this->DestDAO->addEtudiant($rows);
            }

        }


    

    public function insertionFormations ($formations){
        $rows=[];
        foreach($formations as $formation){
            $rows[]= $formation->toDict();
        }
        if(!empty($rows)){  
            $this->DestDAO->addFormation($rows);
        }
    }

    public function insertionRCUEs($rcues){
        $rows=[];
        foreach($rcues as $rcue){
            $rows[]= $rcue->toDict();
        }
        if(!empty($rows)){
            $this->DestDAO->addRCUE($rows);
        }
    }
    public function insertionUEs($ues){ // il faut cree model UE
        $rows=[];
        foreach($ues as $ue){
            $rows[]= $ue->toDict();
        }
        if(!empty($rows)){
            $this->DestDAO->addUE($rows);
        }
    }

    public function insertionFormsemestres($formsemestres){
        $rows=[];
        foreach($formsemestres as $formsemestre){
            $rows[]= $formsemestre->toDict();
        }
        if(!empty($rows)){
            $this->DestDAO->addFormSemestre ($rows);
        }
    }


  


}
