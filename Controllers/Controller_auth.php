<?php 
require_once("Services/Service_auth");
class Controller_auth extends Controller { 

    private Service_auth $service_auth;
    public function __construct( Service_auth $service_auth ) {
        $this->service_auth = $service_auth;}

    public function action_default() {

        session_start();

        if ($this->service_auth->isLogged()) {
            
            return $this->render("admin");
        }

        else { return $this->render("login");}
    }


    public function login() {
        session_start();
        $msg_error = "";
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username=$_POST['username'];
            $password=$_POST['password'];
            $user= $this->service_auth->login($username,$password);
            if ($user) {

                $_SESSION["logged"] = true ;
                $_SESSION["username"] = $user->username ;
                $_SESSION["role"] = $user->role;

                header("Location: index.php?controller=admin&action=default");
                exit;
            }
        
            else {  
                $msg_error="Identifiants ou mot de passe incorrect"; 
                return $this->render("login",["msg_error"=>$msg_error]);
            }
        }

        return $this->render("login");


}
    public function logout() {
        
        session_unset();
        session_destroy();
        header("Location: ?controller=auth&action=default");
        exit;
        
    
    }




}




?>