<?php

class Parcours {
    private int $parcours_id;
    private String $code;
    private String $libelle;
    private int $formation_id;

    public function __construct(array $dict) {
        $this->parcours_id = $dict['parcours_id'];
        $this->code = $dict['code'];
        $this->libelle = $dict['libelle'];
        $this->formation_id = $dict['formation_id'];
    }

    /* GETTERS */

    public function getParcoursId(): int {
        return $this->parcours_id;
    }

    public function getCode(): String {
        return $this->code;
    }

    public function getLibelle(): String {
        return $this->libelle;
    }

    public function getFormationId(): int {
        return $this->formation_id;
    }

    /* OTHER METHODS */

    public function toDict(): array {
        return array(
            'parcours_id' => $this->parcours_id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'formation_id' => $this->formation_id
        );
    }
}
