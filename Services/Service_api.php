<?php

include_once __DIR__ . "/../Models/StatsDAO.php";
class Service_api {
    private StatsDAO $dao;
    public function __construct (?StatsDAO $dao=null){
        $this->dao=$dao ?? StatsDAO::getModel();

    }

    public function ChaineVide(string $chaine){
        $chaine=trim((string)$chaine);
        if ($chaine ===""){
            throw new InvalidArgumentException("Valeur null");
        }
        return $chaine;
    }
    public function ExigeAnnee($annee){
        $annee=trim((string)$annee);
        if (!preg_match('/^\d{4}(-\d{4})?$/',$annee)){
            throw new InvalidArgumentException("Annee invalide");

        }
        return $annee;

    }

    public function getEleveParFormation($formation){
        $formation=$this->ChaineVide($formation);
        return $this->dao->getNbEleveParFormation($formation);

    }

    public function getNbEleveParAnnee($annee){
        $annee=$this->ExigeAnnee($annee);
        return $this->dao->getNbEleveParAnnee($annee);

    }

    public function getNbEleveParParcours($parcours){
        $parcours=$this->ChaineVide($parcours);
        return $this->dao->getNbEleveParParcours($parcours);

    }
     public function getNbRepartitionUEADMISParParcours($parcours){
        $parcours=$this->ChaineVide($parcours);
        return $this->dao->getNbRepartitionUEADMISParParcours($parcours);

    }

     public function getNbRepartitionUEADMISParFormation($formation){
        $formation=$this->ChaineVide($formation);
        return $this->dao->getNbRepartitionUEADMISParFormation($formation);

    }

    public function getNbRepartitionUEADMISParAnnee($annee){
        $annee=$this->ExigeAnnee($annee);
        return $this->dao->getNbRepartitionUEADMISParAnnee($annee);

    }
    public function getEffectifTotalPrecise($formation, $annee){
    $annee=$this->ExigeAnnee($annee);
    $formation=$this->ChaineVide($formation);
    return $this->dao->getEffectifTotalPrecise($formation,$annee);

    }

  

    
}








?>