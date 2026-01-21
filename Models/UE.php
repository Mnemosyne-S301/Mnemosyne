<?php

/**
 * @package Model
 */
class UE {
    private int $ue_id;
    private int $formsemestre_id;
    private string $nomCompetence;

    public function __construct(array $dict) {
        $this->ue_id = $dict['ue_id'];
        $this->formsemestre_id = $dict['formsemestre_id'];
        $this->nomCompetence = $dict['nomCompetence'];
    }

    /* GETTERS */

    public function getUeId(): int {
        return $this->ue_id;
    }

    public function getFormsemestreId(): int {
        return $this->formsemestre_id;
    }

    public function getNomCompetence(): string {
        return $this->nomCompetence;
    }

    /* OTHER METHODS */

    /**
     * Convertit l'objet en tableau associatif
     */
    public function toDict(): array {
        return array(
            'ue_id' => $this->ue_id,
            'formsemestre_id' => $this->formsemestre_id,
            'nomCompetence' => $this->nomCompetence
        );
    }
}
?>
