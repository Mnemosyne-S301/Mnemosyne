<?php
class Formsemestre {
    private int $formsemestre_id;
    private string $titre;
    private ?int $semestre_num;
    private ?string $date_debut; // DATE
    private ?string $date_fin;   // DATE
    private ?string $titre_long;
    private ?string $etape_apo;
    private int $anneeformation_id;

    public function __construct(array $d) {
        $this->formsemestre_id = (int)$d['formsemestre_id'];
        $this->titre = (string)$d['titre'];
        $this->semestre_num = isset($d['semestre_num']) ? (int)$d['semestre_num'] : null;
        $this->date_debut = $d['date_debut'] ?? null;
        $this->date_fin = $d['date_fin'] ?? null;
        $this->titre_long = $d['titre_long'] ?? null;
        $this->etape_apo = $d['etape_apo'] ?? null;
        $this->anneeformation_id = (int)$d['anneeformation_id'];
    }

    public function toDict(): array {
        return [
            'formsemestre_id' => $this->formsemestre_id,
            'titre' => $this->titre,
            'semestre_num' => $this->semestre_num,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'titre_long' => $this->titre_long,
            'etape_apo' => $this->etape_apo,
            'anneeformation_id' => $this->anneeformation_id,
        ];
    }
}
