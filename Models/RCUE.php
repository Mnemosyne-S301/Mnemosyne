<?php

class RCUE {
    private String $nomCompetence;
    private int $niveau;
    private int $ordre_anneeFormation;
    private String $code_parcours;
    private int $formation_id;

    public function __construct(array $dict) {
        $this->nomCompetence = $dict['nomCompetence'];
        $this->niveau = $dict['niveau'];
        $this->ordre_anneeFormation = $dict['ordre_anneeFormation'];
        $this->code_parcours = $dict['code_parcours'];
        $this->formation_id = $dict['formation_id'];
    }

    /* GETTERS */

    public function getNomCompetence(): String {
        return $this->nomCompetence;
    }

    public function getNiveau(): int {
        return $this->niveau;
    }

    public function getOrdreAnneeFormation(): int {
        return $this->ordre_anneeFormation;
    }

    public function getCodeParcours(): String {
        return $this->code_parcours;
    }

    public function getFormationId(): int {
        return $this->formation_id;
    }

    /* OTHER METHODS */

    public function toDict(): array {
        return array(
            'nomCompetence' => $this->nomCompetence,
            'niveau' => $this->niveau,
            'ordre_anneeFormation' => $this->ordre_anneeFormation,
            'code_parcours' => $this->code_parcours,
            'formation_id' => $this->formation_id
        );
    }
}
?>