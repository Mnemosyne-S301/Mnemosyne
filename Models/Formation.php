<?php

class Formation{
    private $label;
    private $departement;
    private $startDate;
    private $rcues;
    private $competences;

    public function __construct($dict){
        $this->label = $dict['label'];
        $this->departement = $dict['departement'];
        $this->startDate = $dict['startDate'];
        $this->rcues = $dict['rcues'];
        $this->competences = $dict['competences'];
    }
}