<?php
class AnneeFormation {
    private ?int $anneeformation_id; // SERIAL en DB, pas nécessaire à l’insert
    private int $ordre;
    private int $parcours_id;

    public function __construct(array $d) {
        $this->anneeformation_id = isset($d['anneeformation_id']) ? (int)$d['anneeformation_id'] : null;
        $this->ordre = (int)$d['ordre'];
        $this->parcours_id = (int)$d['parcours_id'];
    }

    public function toDict(): array {
        // addAnneeFormation() attend seulement ordre + parcours_id
        return [
            'ordre' => $this->ordre,
            'parcours_id' => $this->parcours_id,
        ];
    }
}
