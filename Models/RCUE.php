<?php

class RCUE {
    private String $nomCompetence;
    private int $niveau;
    private int $anneeformation_id;

    public function __construct(array $dict) {
        $this->nomCompetence = $dict['nom_competence'];
        $this->niveau = $dict['niveau'];
        $this->anneeformation_id = $dict['annee_formation_id'];
    }

    /* GETTERS */

    public function getNomCompetence(): String {
        return $this->nomCompetence;
    }

    public function getNiveau(): int {
        return $this->niveau;
    }

    public function getAnneeFormationId(): int {
        return $this->anneeformation_id;
    }

    /* OTHER METHODS */

    /**
     * Convertit l'objet en tableau associatif
     */
    public function toDict(): array {
        return array(
            'nom_competence' => $this->nomCompetence,
            'niveau' => $this->niveau,
            'annee_formation_id' => $this->anneeformation_id
        );
    }
}
?>