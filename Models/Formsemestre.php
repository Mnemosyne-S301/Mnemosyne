<?php
class Formsemestre {
    private int $formsemestre_id;
    private string $titre;
    private int $semestre_num;
    private string $date_debut;
    private string $date_fin;
    private string $titre_long;
    private string $etape_apo;
    private int $anneeformation_id;

    public function __construct($dict){
        $this->formsemestre_id = $dict['formsemestre_id'];
        $this->titre = $dict['titre'];
        $this->semestre_num = $dict['semestre_num'];
        $this->date_debut = $dict['date_debut'];
        $this->date_fin = $dict['date_fin'];
        $this->titre_long = $dict['titre_long'];
        $this->etape_apo = $dict['etape_apo'];
        $this->anneeformation_id = $dict['anneeformation_id'];
    }

    /* GETTERS */

    public function getFormsemestreId(): int {
        return $this->formsemestre_id;
    }

    public function getTitre(): string {
        return $this->titre;
    }

    public function getSemestreNum(): int {
        return $this->semestre_num;
    }

    public function getDateDebut(): string {
        return $this->date_debut;
    }

    public function getDateFin(): string {
        return $this->date_fin;
    }

    public function getTitreLong(): string {
        return $this->titre_long;
    }

    public function getEtapeApo(): string {
        return $this->etape_apo;
    }

    public function getAnneeformationId(): int {
        return $this->anneeformation_id;
    }

    /* OTHER METHODS */

    public function toDict() : array {
        return array(
            'formsemestre_id' => $this->formsemestre_id,
            'titre' => $this->titre,
            'semestre_num' => $this->semestre_num,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'titre_long' => $this->titre_long,
            'etape_apo' => $this->etape_apo,
            'anneeformation_id' => $this->anneeformation_id
        );
    }
}
?>