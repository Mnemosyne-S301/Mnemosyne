<?php

interface SourceDataDAO {
    public function findall_etudiant();
    public function findall_ue();
    public function findall_departement();
    public function findall_formsemestre();
    public function findall_decision();
    public function findformation_by_accronyme(string $accronyme);
    public function finddepartement_by_accronyme(string $accronyme);
    public function findformsemestre_by_id(string $id);

    
}

?>