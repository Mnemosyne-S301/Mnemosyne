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

    /* Getters */
    public function getLabel(){
        return $this->label;
    }

    public function getDepartement(){
        return $this->departement;
    }

    public function getStartDate(){
        return $this->startDate;
    }

    public function getRcues(){
        return $this->rcues;
    }

    public function getCompetences(){
        return $this->competences;
    }

    /* Other methods */
    public function toDict() : array {
        return array(
                    'label' => $this->label,
                    'departement' => $this->departement,
                    'startDate' => $this->startDate,
                    'rcues' => $this->rcues,
                    'competences' => $this->competences
                );

    }
}
?>