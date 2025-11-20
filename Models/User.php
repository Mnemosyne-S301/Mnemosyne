<?php
use PDO ;
class User {
public int $id_user;
public $username;
private String $password;
public string $role;
public function __construct(array $data) {
    $this->id_user = $data["id_user"];
    $this->username = $data["username"];
    $this->role = $data["role"];
    $this->password = $data["password"];
}  

public function getMdp() : string { return $this->password;}


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
}







?>