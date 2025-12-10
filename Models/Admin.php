<?php

class Admin{

    private $userName;
    private $userID;

    public function __construct($userName, $userID){
        $this->userName = $userName;
        $this->userID = $userID;
    }
}