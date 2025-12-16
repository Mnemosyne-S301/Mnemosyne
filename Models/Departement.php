<?php
class Departement{
    private int $dep_id;
    private String $accronyme;
    private String $description;
    private bool $visible;
    private String $date_creation;
    private String $nom_dep;

    public function __construct($dict){
        $this->dep_id = $dict['dep_id'];
        $this->accronyme = $dict['accronyme'];
        $this->description = $dict['description'];
        $this->visible = $dict['visible'];
        $this->date_creation = $dict['date_creation'];
        $this->nom_dep = $dict['nom_dep'];
    }

    /* GETTERS */

    public function getDepId(): int {
        return $this->dep_id;
    }

    public function getacronym(): string {
        return $this->acronym;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getVisible(): bool {
        return $this->visible;
    }

    public function getDateCreation(): string {
        return $this->date_creation;
    }

    public function getNomDep(): string {
        return $this->nom_dep;
    }

    /* OTHER METHODS */

    public function toDict() : array {
        return array(
                    'dep_id' => $this->dep_id,
                    'accronyme' => $this->accronyme,
                    'description' => $this->description,
                    'visible' => $this->visible,
                    'date_creation' => $this->date_creation,
                    'nom_dep' => $this->nom_dep
                );
    }
}
?>