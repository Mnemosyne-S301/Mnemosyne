<?php

require_once __DIR__ ."/Departement.php";
require_once __DIR__ ."/DAO.php";


class DepartementDAOApi extends DAO {
    public function __construct() { $this->getToken();}

    


    public function findall() {return $this->get("/departements");}
    public function findby_accronyme_dep(string $accronyme) {return $this->get("/departements/" . $accronyme);}

}

class DepartementDAODB extends DAO {
    public function __construct()  {$this->__getdbconnction();}
    
    public function findall(){
        $conn=$this->__getdbconnction();
        $departements =$conn->prepare("SELECT * FROM Formation;");
        $departements->execute();
        $departements=$departements->fetchAll(PDO::FETCH_ASSOC);
        $instances=[];
        foreach ($departements as  $departement ) {
            $instances[]=new Departement($departement);}
            

        return $instances;
            


    }
    public function findby_accronyme_dep(string $accronyme) {
           
        $conn=$this->__getdbconnction();
        $departement =$conn->prepare("SELECT * FROM Formation WHERE accronyme= :accronyme;");
        $departement->execute(["accronyme"=> $accronyme]);
        $departement=$departement->fetch(PDO::FETCH_ASSOC);
        
        if ($departement){return new Departement($departement);}
        else {echo "Aucun Departement avec cette accronyme !";}
            


    }




    
}
?>