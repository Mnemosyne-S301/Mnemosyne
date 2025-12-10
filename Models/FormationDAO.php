<?php

require_once __DIR__ ."/Formation.php";
require_once __DIR__ ."/DAO.php";


class FormationDaoApi extends DAO {
    public function __construct() { $this->getToken();}

    


    public function findall() {return $this->get("/fornmation");}
    public function findby_accronyme_formation(string $accronyme) {return $this->get("/formations/" . $accronyme);}

}

class FormationDaoBD extends DAO {
    public function __construct()  {$this->__getdbconnction();}
    
    public function findall(){
        $conn=$this->__getdbconnction();
        $formations =$conn->prepare("SELECT * FROM Formation;");
        $formations->execute();
        $formations=$formations->fetchAll(PDO::FETCH_ASSOC);
        $instances=[];
        foreach ($formations as  $formation ) {
            $instances[]=new Formation($formation);}
            

        return $instances;
            


    }
    public function findby_accronyme_fromation(string $accronyme) {
           
        $conn=$this->__getdbconnction();
        $formation =$conn->prepare("SELECT * FROM Formation WHERE accronyme= :accronyme;");
        $formation->execute(["accronyme"=> $accronyme]);
        $formation=$formation->fetch(PDO::FETCH_ASSOC);
        
        if ($formation){return new Formation($formation);}
        else {echo "Aucune formation avec cette accronyme !";}
            


    }




    
}
?>