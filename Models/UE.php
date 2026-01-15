<?php
class UE {
    private int $ue_id;
    private int $rcue_id;
    private int $formsemestre_id;

    public function __construct(array $d) {
        $this->ue_id = (int)$d['ue_id'];
        $this->rcue_id = (int)$d['rcue_id'];
        $this->formsemestre_id = (int)$d['formsemestre_id'];
    }

    public function toDict(): array {
        return [
            'ue_id' => $this->ue_id,
            'rcue_id' => $this->rcue_id,
            'formsemestre_id' => $this->formsemestre_id,
        ];
    }
}
