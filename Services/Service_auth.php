<?php 
require_once __DIR__ . "/../Models/UserDAO.php";

class Service_auth {
    private UserDAO $dao ;   

    public function __construct() {
        $this->dao = new UserDAO(); 
        
    }

    public function login($username, $password)  {
        
        $user= $this->dao->authenticate($username,$password);
        if ($user){return $user;}
        else {return false ;}
    }

    public function isLogged() : bool {
      return  isset($_SESSION["logged"]) && $_SESSION["logged"] === true;

     
        }

    public function logout()  {
         session_destroy();}

public function isAdmin(){
    return $this->isLogged() && isset($_SESSION['role']) && $_SESSION["role"]==="admin";
}




    

}


?>