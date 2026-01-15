<?php
class RCUE {
    private ?int $rcue_id; // SERIAL en DB
    private string $nomCompetence;
    private ?int $niveau;
    private int $anneeformation_id;

    public function __construct(array $d) {
        $this->rcue_id = isset($d['rcue_id']) ? (int)$d['rcue_id'] : null;
        $this->nomCompetence = (string)$d['nomCompetence'];
        $this->niveau = isset($d['niveau']) ? (int)$d['niveau'] : null;
        $this->anneeformation_id = (int)$d['anneeformation_id'];
    }

    public function toDict(): array {
        // addRCUE() n’insère pas rcue_id, seulement nomCompetence/niveau/anneeformation_id
        return [
            'nomCompetence' => $this->nomCompetence,
            'niveau' => $this->niveau,
            'anneeformation_id' => $this->anneeformation_id,
        ];
    }
}
