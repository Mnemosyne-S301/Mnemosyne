<?php 
require_once __DIR__ . "/User.php";
require_once __DIR__ . "/DB.php";

class UserDAO {
    private PDO $pdo;   

    public function __construct() {
        $this->pdo = DB::get(); }




public static function findAll() : array {
    $pdo=DB::get();
    $requete = $pdo->prepare("SELECT * FROM users ");
    
    $requete->execute() ;
    $data=$requete->fetchAll() ;
    $Users=[];

    if ($data) {
        foreach ($data as $user_row) {
            array_push($Users,new User($user_row)) ;
        }
        return $Users;  
            
    }
    else {
        return [] ;
    }
}


public static function findbyname(string $username) : ?User {
    $pdo=DB::get();
    $requete = $pdo->prepare("SELECT * FROM users WHERE username=:username");
    $requete->bindParam(":username", $username, PDO::PARAM_STR) ;
    $requete->execute() ;
    $data=$requete->fetch() ;


    if ($data) {
        return new User($data);
    }
    else {
        return null ;
    }





}
public function createUser(string $username, string $password)  {
    $pdo=DB::get();
    $requete = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $requete->bindParam(":username", $username, PDO::PARAM_STR) ;
    $requete->bindParam(":password", $password, PDO::PARAM_STR) ;
    $requete->execute() ;
    
}

}
?>