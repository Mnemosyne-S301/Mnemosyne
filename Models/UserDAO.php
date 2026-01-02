<<?php 
require_once "User.php";

class UserDAO {
    private PDO $pdo;   

    public function __construct() {
        $this->pdo = DB::get(); }




public  function findAll() : array {
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


public  function findbyname(string $username) : ?User {
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

public function findbyrole(string $role){
    $pdo=DB::get();
    $requete = $pdo->prepare("SELECT * FROM users WHERE role=:role");
    $requete->bindParam(":role", $role, PDO::PARAM_STR) ;
    $requete->execute() ;
    $data=$requete->fetchAll(PDO::FETCH_ASSOC) ;

    $users=[];
    foreach ($data as $row){
        $users[]=new User($row);
    }
    return $users;
}



public function createUser(string $username, string $password, string $role)  {
    $pdo=DB::get();
    $requete = $pdo->prepare("INSERT INTO users (username,password,role) VALUES (:username, :password,:role)");
    $requete->bindParam(":username", $username, PDO::PARAM_STR) ;
    $requete->bindParam(":password", $password, PDO::PARAM_STR) ;
    $requete->bindParam(":role", $role, PDO::PARAM_STR) ;

    return $requete->execute() ;
}

public function deleteById(int $id){
    $pdo=DB::get();
    $requete = $pdo->prepare("DELETE FROM users WHERE id =:id");
    $requete->bindParam(":id", $id, PDO::PARAM_INT) ;
    return $requete->execute() ;
}
    
}


?>