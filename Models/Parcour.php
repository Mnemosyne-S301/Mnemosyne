<?php
class Parcours {
    private int $parcours_id;
    private string $code;
    private string $libelle;
    private int $formation_id;

    public function __construct(array $d) {
        $this->parcours_id = (int)$d['parcours_id'];
        $this->code = (string)$d['code'];
        $this->libelle = (string)$d['libelle'];
        $this->formation_id = (int)$d['formation_id'];
    }

    public function toDict(): array {
        return [
            'parcours_id' => $this->parcours_id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'formation_id' => $this->formation_id,
        ];
    }
}
