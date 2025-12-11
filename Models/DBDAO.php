<?php 
require_once __DIR__ . "DB.php";

abstract class DAO {
private PDO $pdo;



    protected function __getdbconnction(): PDO {
        $this->pdo = DB ::get();
        return $this->pdo;  

    }



   abstract public function findall();


}


?>