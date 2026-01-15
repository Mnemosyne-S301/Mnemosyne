<?php

class Formation{
    private int $formation_id;
    private String $accronyme;
    private String $titre;
    private int $version;
    private String $formation_code;
    private int $type_parcours;
    private String $titre_officiel;
    private String $commentaire;
    private String $code_specialite;
    private int $dep_id;

    public function __construct(array $dict) {
        $this->formation_id = $dict['formation_id'];
        $this->accronyme = $dict['accronyme'];
        $this->titre = $dict['titre'];
        $this->version = $dict['version'];
        $this->formation_code = $dict['formation_code'];
        $this->type_parcours = $dict['type_parcours'];
        $this->titre_officiel = $dict['titre_officiel'];
        $this->commentaire = $dict['commentaire'];
        $this->code_specialite = $dict['code_specialite'];
        $this->dep_id = $dict['dep_id'];
    }

    /* GETTERS */

    public function getFormationId(): int {
        return $this->formation_id;
    }

    public function getAccronyme(): String {
        return $this->accronyme;
    }

    public function getTitre(): String {
        return $this->titre;
    }

    public function getVersion(): int {
        return $this->version;
    }

    public function getFormationCode(): String {
        return $this->formation_code;
    }

    public function getTypeParcours(): int {
        return $this->type_parcours;
    }

    public function getTitreOfficiel(): String {
        return $this->titre_officiel;
    }

    public function getCommentaire(): String {
        return $this->commentaire;
    }

    public function getCodeSpecialite(): String {
        return $this->code_specialite;
    }

    public function getDepId(): int {
        return $this->dep_id;
    }

    /* OTHER METHODS */

    public function toDict(): array {
        return array(
            'formation_id' => $this->formation_id,
            'accronyme' => $this->accronyme,
            'titre' => $this->titre,
            'version' => $this->version,
            'formation_code' => $this->formation_code,
            'type_parcours' => $this->type_parcours,
            'titre_officiel' => $this->titre_officiel,
            'commentaire' => $this->commentaire,
            'code_specialite' => $this->code_specialite,
            'dep_id' => $this->dep_id
        );
}
}