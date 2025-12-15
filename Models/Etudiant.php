<?php
class Etudiant{
    private String $code_nip;
    private String $etat;

    public function __construct($dict){
        $this->code_nip = $dict['code_nip'];
        $this->etat = $dict['etat'];
    }

    /* GETTERS */

    public function getCodeNip() : String {
        return $this->code_nip;
    }

    public function getEtat() : String {
        return $this->etat;
    }

    /* OTHER METHODS */

    public function toDict() : array {
        return array(
                    'code_nip' => $this->code_nip,
                    'etat' => $this->etat
                );
    }
}
?>