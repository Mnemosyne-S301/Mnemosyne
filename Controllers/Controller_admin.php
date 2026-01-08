<?php
require_once __DIR__ . "/../Services/Service_auth.php";
require_once __DIR__ . "/../Services/Service_admin.php";
class Controller_admin extends Controller {

    /* ajouter les autres méthodes ici sous la forme action_quelquechose */
    private Service_auth $service_auth;
    private Service_admin $service_admin;

    public function __construct(Service_admin $service_admin, Service_auth $service_auth ){
        $this->service_auth= $service_auth;
        $this->service_admin=$service_admin;
    }


    public function requireAdmin(){
         if (session_status()=== PHP_SESSION_NONE){session_start();}
         if (!$this->service_auth->isAdmin()){
            http_response_code(403);
            exit( "Acces interdit (vous devez etre ADMIN)");
         }
    }
    
    public function action_default() {
        $this->requireAdmin();
        $admins=$this->service_admin->listAdmin();

        return $this->render("admin",['admins'=>$admins]);
        /* Méthode abstraite à reimplementer sinon ça marchera pas */
    }

    public function addAdmin(){
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD']==="POST"){
            $username=trim($_POST['username']);
            $password=$_POST['password'];
            if ($username != "" && $password != ""){
                $this->service_admin->addAdmin($username,$password);
            }

        }
        header("Location: index.php?controller=admin&action=default");
        exit;

    }

        public function delAdmin(){
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD']==="POST"){
            $id=(int)$_POST['id'];
            
            if ($id>0){
                $this->service_admin->deleteAdmin($id);
            }

        }
        header("Location: index.php?controller=admin&action=default");
        exit;

    }
    
    
}
?>