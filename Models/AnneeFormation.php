<?php

/**
 * @package Model
 */
class AnneeFormation {
    private int $ordre;
    private int $parcours_id;

    public function __construct(array $dict) {
        $this->ordre = $dict['ordre'];
        $this->parcours_id = $dict['parcours_id'];
    }

    /* GETTERS */

    public function getOrdre(): int {
        return $this->ordre;
    }

    public function getParcoursId(): int {
        return $this->parcours_id;
    }

    /* OTHER METHODS */

    public function toDict(): array {
        return array(
            'ordre' => $this->ordre,
            'parcours_id' => $this->parcours_id
        );
    }
}

?>