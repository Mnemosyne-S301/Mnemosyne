<?php
require_once __DIR__ . '/../Models/StatsDAO.php';
require_once __DIR__ . '/../Models/ScolariteDAO.php';

/**
 * Classe Service_stats.
 * Rôle : Couche de logique métier (Business Logic) entre le Contrôleur
 * et le Data Access Object (DAO).
 * Elle gère les calculs et le formatage des données brutes reçues du DAO.
 * 
 * Utilise deux DAO :
 * - StatsDAO : pour les statistiques agrégées (BDD stats)
 * - ScolariteDAO : pour les données individuelles des étudiants (BDD scolarite)
 */
class Service_stats {
    
    /**
     * @var StatsDAO $statsDao DAO pour les statistiques agrégées (effectifs, répartition UE).
     */
    private StatsDAO $statsDao;

    /**
     * @var ScolariteDAO $scolariteDao DAO pour les données individuelles (cohortes Sankey).
     */
    private ScolariteDAO $scolariteDao;


    /**
     * Constructeur.
     * Initialise le Service en récupérant les instances des DAO (Singleton).
     */
    public function __construct(){
        $this->statsDao = StatsDAO::getModel();
        $this->scolariteDao = ScolariteDAO::getModel();
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
        return $this->statsDao->getEffectifTotalPrecise($Formation, $Annee);
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

        // Initialisation des clés pour garantir qu'elles existent, même si = 0, la valeur '6' est une valeur de test non pertinente ici et pourrait être source de confusion.
        $res['ue_6'] = 0;
        $res['ue_5'] = 0;
        $res['ue_4'] = 0;
        $res['ue_3'] = 0;
        $res['ue_2'] = 0;
        $res['ue_1'] = 0;

        // Récupération des données brutes de répartition du DAO
        $list=$this->statsDao->getNbRepartitionUEADMISParFormation($Formation);

        foreach($list as $ligne){
            
            // Si l'année de la ligne correspond à l'année demandée
            if((int)$ligne['annee_scolaire'] == (int)$Annee){
                
                // utilise un switch pour affecter le nombre d'élèves (nb_eleves)
                // à la bonne clé ('ue_X') dans le tableau de résultat.
                switch($ligne['nb_ue_validees']){
                    case 6:
                        $res['ue_6']+=(int)$ligne['nb_eleves'];
                        break;

                    case 5:
                        $res['ue_5']+=(int)$ligne['nb_eleves'];
                        break;
                    
                    case 4:
                        $res['ue_4']+=(int)$ligne['nb_eleves'];
                        break;

                    case 3:
                        $res['ue_3']+=(int)$ligne['nb_eleves'];
                        break;

                    case 2:
                        $res['ue_2']+=(int)$ligne['nb_eleves'];
                        break;

                    case 1:
                        $res['ue_1']+=(int)$ligne['nb_eleves'];
                        break;

                    }
            }
        }
        
        return $res;
    }

    public function getSankeyCohorteDepuisAnnee(int $anneeDepart, string $formation , ?string $parcours = null): array
{
    $annees = [$anneeDepart, $anneeDepart + 1, $anneeDepart + 2];

    $formatterPourJS = function(array $lignes): array {
        return array_map(function($ligne) {
            return [
                'etudid' => (string)$ligne['etudid'],
                'etat' => $ligne['etat'] ?? null,
                'annee' => [
                    'ordre' => (int)$ligne['ordre'],                 // BUT1  BUT2 BUT3
                    'code' => $ligne['code'],                        // ADM  RED  AJ 
                    'annee_scolaire' => (string)$ligne['annee_scolaire'],
                ],
            ];
        }, $lignes);
    };

    $donnees = [];

    foreach ($annees as $annee) {
        // Passer l'année de départ comme année de cohorte pour suivre les mêmes étudiants
        $lignes = $this->scolariteDao->getCohorteParAnneeEtFormation($annee, $formation, $anneeDepart);
        $donnees[(string)$annee] = $formatterPourJS($lignes);
    }

    return [
        'annee_depart' => $anneeDepart,
        'annees' => $annees,
        'data' => $donnees
    ];
}

    /**
     * Récupère la cohorte brute pour une année et formation données.
     * Utilisé pour les statistiques (diplômés, abandons, en cours).
     *
     * @param int $annee L'année de départ de la cohorte
     * @param string $formation Le code de la formation
     * @return array Liste des étudiants avec leur état
     */
    public function getCohorteParAnneeEtFormation(int $annee, string $formation): array {
        return $this->scolariteDao->getCohorteParAnneeEtFormation($annee, $formation);
    }

}