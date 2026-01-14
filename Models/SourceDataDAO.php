<?php

interface SourceDataDAO {
    public function findall_etudiant();
    public function findall_ue();
    public function findall_departement();
    public function findall_formsemestre();
    public function findall_decision();

    public function findall_rcue(); 
    public function findformation_by_id(string $id);
    public function finddepartement_by_accronyme(string $accronyme);
    public function findformsemestre_by_id(string $id);

    public function findDecisionsByFormsemestre(string $id);
    public function findEtudiantsByDepartement(string $dept);
    public function findFormsemestresByFormation(string $acronyme);
    public function findUEByCode(string $code);
}

?>