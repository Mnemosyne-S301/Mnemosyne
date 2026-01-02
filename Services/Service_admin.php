<?php
require_once __DIR__ . "/../Models/UserDAO.php";
class Service_admin {
    private UserDAO $dao;

    public function __construct(){
        $this->dao = new UserDAO();
    }

    public function listAdmin() {
        return $this->dao->findbyrole("admin"); // en vrai findall ca passe aussi car y a que des admins
    }

    public function addAdmin(string $username, string $password){
        if ($this->dao->findbyname($username)){return false ;}
        $hash=password_hash($password, PASSWORD_BCRYPT);
        return $this->dao->createUser($username,$hash,"admin");

    }
    
    public function deleteAdmin (int $id){
        return $this->dao->deleteById($id);
    }
}
?>