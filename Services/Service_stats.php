<?php
require_once 'StatsDAO.php';

/**
 * Classe Service_stats.
 * Rôle : Couche de logique métier (Business Logic) entre le Contrôleur
 * et le Data Access Object (DAO).
 * Elle gère les calculs et le formatage des données brutes reçues du DAO.
 */
class Service_stats {
    
    /**
     * @var StatsDAO $dao Instance du Data Access Object pour interagir avec la BDD.
     */
    private StatsDAO $dao;


    /**
     * Constructeur.
     * Initialise le Service en récupérant l'unique instance du StatsDAO (Singleton).
     */
    public function __construct(){
        $this->dao =  StatsDAO::getModel();

    }

    /**
     * Récupère l'effectif total d'étudiants pour une formation et une année spécifiques.
     * Cette méthode est un simple "passe-plat" vers la méthode SQL optimisée du DAO.
     *
     * @param string $Formation Le nom de la formation.
     * @param int|string $Annee L'année scolaire.
     * @return int Le nombre total d'étudiants.
     */
    public function recupererEffectifParFormationAnnee($Formation,$Annee){
        return $this->dao->getEffectifTotalPrecise($Formation, $Annee);
    }


    /**
     * Récupère la répartition des UE validées (ADMIS) pour une formation et une année.
     * Le résultat est formaté pour être directement utilisable par une librairie graphique
     * (Histogramme des réussites).
     *
     * @param string $Formation Le nom de la formation.
     * @param int|string $Annee L'année scolaire.
     * @return array Tableau associatif où les clés sont 'ue_1' à 'ue_6' et les valeurs
     * sont le nombre d'élèves correspondant.
     */
    public function recupererRepartitionUEADMISParFormation($Formation,$Annee){
        $res=[];

        // Initialisation des clés pour garantir qu'elles existent, même avec une valeur de 0.
        // La valeur '6' est une valeur de test non pertinente ici et pourrait être source de confusion.
        $res['ue_6'] = 0;
        $res['ue_5'] = 0;
        $res['ue_4'] = 0;
        $res['ue_3'] = 0;
        $res['ue_2'] = 0;
        $res['ue_1'] = 0;

        // Récupération des données brutes de répartition du DAO (toutes années et parcours confondus)
        $list=$this->dao->getNbRepartitionUEADMISParFormation($Formation);

        // Parcours de toutes les lignes retournées par la BDD
        foreach($list as $ligne){
            
            // Si l'année de la ligne correspond à l'année demandée
            if((int)$ligne['annee_scolaire'] == (int)$Annee){
                
                // On utilise un switch pour affecter le nombre d'élèves (nb_eleves)
                // à la bonne clé ('ue_X') dans le tableau de résultat.
                switch($ligne['nb_ue_validees']){
                    case 6:
                        $res['ue_6']=$ligne['nb_eleves'];
                        break;

                    case 5:
                        $res['ue_5']=$ligne['nb_eleves'];
                        break;
                    
                    case 4:
                        $res['ue_4']=$ligne['nb_eleves'];
                        break;

                    case 3:
                        $res['ue_3']=$ligne['nb_eleves'];
                        break;

                    case 2:
                        $res['ue_2']=$ligne['nb_eleves'];
                        break;

                    case 1:
                        $res['ue_1']=$ligne['nb_eleves'];
                        break;

                    }
            }
        }
        
        return $res;
    }
}