<<?php 
require_once "UserDAO.php";

class Service_auth {
    private UserDAO $dao ;   

    public function __construct() {
        $this->dao = new UserDAO(); 
        
    }

    public function login($username, $password)  {
        
        $user= $this->dao->findbyname($username);
        if ($user==null) {return false;}
        else {
            if ( password_verify($password, $user->getMdp())){
               
               return $user ;

            }
            else {return false;}
        }
    }

    public function isLogged() : bool {
        if (isset($_SESSION["ADMIN"]) && $_SESSION["ADMIN"] == true) {

            return true ;} 
        return false;

        }

    public function logout()  {
         session_destroy();}

public function isAdmin(){
    return $this->isLogged() && isset($_SESSION['role']) && $_SESSION["role"]==="admin";
}




    

}


?>