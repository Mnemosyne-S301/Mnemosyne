<?php

require_once __DIR__ ."/Etudiant.php";
require_once __DIR__ ."/DAO.php";


class EtudiantDAOApi extends DAO {
    public function __construct() { $this->getToken();}

    


    public function findall() {return $this->get("/etudiants");}
    public function findbyid(string $id) {return $this->get("/etudiants/" . $id);}

}

class EtudiantDAOBd extends DAO {
    public function __construct()  {$this->__getdbconnction();}
    
    public function findall(){
        $conn=$this->__getdbconnction();
        $etudiants =$conn->prepare("SELECT * FROM Etudiant;");
        $etudiants->execute();
        $etudiants=$etudiants->fetchAll(PDO::FETCH_ASSOC);
        $instances=[];
        foreach ($etudiants as  $etudiant) {
            $instances[]=new Etudiant($etudiant);}
            

        return $instances;
            


    }
    public function findbyid(string $id) {
           
        $conn=$this->__getdbconnction();
        $etudiant =$conn->prepare("SELECT * FROM Etudiant WHERE etudiant_id= :etudiant_id;");
        $etudiant->execute(["etudiant_id"=> $id]);
        $etudiant=$etudiant->fetch(PDO::FETCH_ASSOC);
        
        if ($etudiant){return new Etudiant($etudiant);}
        else {echo "Aucun Etudiant avec cette id !";}
            


    }




    
}
?>
