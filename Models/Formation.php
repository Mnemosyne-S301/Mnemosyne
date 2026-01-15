<?php
class Formation {
    private int $formation_id;
    private string $accronyme;
    private string $titre;
    private ?int $version;
    private string $formation_code;
    private ?int $type_parcours;
    private string $titre_officiel;
    private ?string $commentaire;
    private ?string $code_specialite;
    private ?int $dep_id;

    public function __construct(array $d) {
        $this->formation_id = (int)$d['formation_id'];
        $this->accronyme = (string)$d['accronyme'];
        $this->titre = (string)$d['titre'];
        $this->version = isset($d['version']) ? (int)$d['version'] : null;
        $this->formation_code = (string)$d['formation_code'];
        $this->type_parcours = isset($d['type_parcours']) ? (int)$d['type_parcours'] : null;
        $this->titre_officiel = (string)$d['titre_officiel'];
        $this->commentaire = $d['commentaire'] ?? null;
        $this->code_specialite = $d['code_specialite'] ?? null;
        $this->dep_id = isset($d['dep_id']) ? (int)$d['dep_id'] : null; // FK optionnelle
    }

    public function toDict(): array {
        return [
            'formation_id' => $this->formation_id,
            'accronyme' => $this->accronyme,
            'titre' => $this->titre,
            'version' => $this->version,
            'formation_code' => $this->formation_code,
            'type_parcours' => $this->type_parcours,
            'titre_officiel' => $this->titre_officiel,
            'commentaire' => $this->commentaire,
            'code_specialite' => $this->code_specialite,
            'dep_id' => $this->dep_id,
        ];
    }
}
