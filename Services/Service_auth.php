<?php 
require_once __DIR__ . "/../Models/UserDAO.php";
require_once __DIR__ . "/../Models/DB.php";

/**
 * @package Service
 */
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
        if (isset($_SESSION["logged"]) && $_SESSION["logged"] == true) {
            return true;
        }
        return false;
    }

    public function logout()  {
         session_destroy();}




    public function hashMdp(String $password) : string {
        return  password_hash($password, PASSWORD_BCRYPT);}

    
    public function createUser ( string $username, string $password)  {
        $password= password_hash($password, PASSWORD_BCRYPT);
        if ($this->dao->findbyname($username)) {
            echo "Cet utiulisateur existe deja ";
            return false;}
        else {   
        return $this->dao->createUser($username, $password);

    }

}
}


?>