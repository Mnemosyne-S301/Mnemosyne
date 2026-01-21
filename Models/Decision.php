<?php
/**
 * @package Model
 */
class Decision {
    private string $code;
    private string $code_nip;
    private int $annee_scolaire;
    private int $formsemestre_id;

    public function __construct($dict){
        $this->code = $dict['code'];
        $this->code_nip = $dict['code_nip'];
        $this->annee_scolaire = $dict['annee_scolaire'];
        $this->formsemestre_id = $dict['formsemestre_id'];
    }

    /* GETTERS */

    public function getCode(): string {
        return $this->code;
    }

    public function getCodeNip(): string {
        return $this->code_nip;
    }

    public function getAnneeScolaire(): int {
        return $this->annee_scolaire;
    }

    public function getFormsemestreId(): int {
        return $this->formsemestre_id;
    }

    /* OTHER METHODS */

    public function toDict() : array {
        return array(
            'code' => $this->code,
            'code_nip' => $this->code_nip,
            'annee_scolaire' => $this->annee_scolaire,
            'formsemestre_id' => $this->formsemestre_id
        );
    }
}
?>