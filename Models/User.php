<?php
use PDO ;
class User {
public int $id_user;
public string $username;
public string $role;
public function __construct(array $data) {
    $this->id_user = (int)$data["id"];
    $this->username = $data["username"];
    $this->role = $data["role"];

}  


}





?>