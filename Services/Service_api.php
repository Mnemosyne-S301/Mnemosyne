<?php
require_once __DIR__ . '/../Models/StatsDAO.php';

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
        $this->dao = StatsDAO::getModel();
    }

    /**
     * Écrit un message de log local dans `logs/php_local.log`.
     */
    private function logLocal(string $msg): void {
        $logFile = __DIR__ . '/../logs/php_local.log';
        @mkdir(dirname($logFile), 0777, true);
        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }

    /**
     * Retourne la cohorte brute pour une année et une formation (tableau d'étudiants)
     * @param int $annee L'année scolaire
     * @param string $formation L'acronyme de la formation
     * @return array Tableau associatif des étudiants de la cohorte
     */
    public function getCohorteParAnneeEtFormation(int $annee, string $formation): array {
        try {
            $result = $this->dao->getCohorteParAnneeEtFormation($annee, $formation);
            $this->logLocal("[DEBUG] getCohorteParAnneeEtFormation annee=$annee formation=$formation count=" . count($result));
            return $result;
        } catch (Exception $e) {
            $this->logLocal("[ERROR] getCohorteParAnneeEtFormation: " . $e->getMessage());
            // Retourner un tableau vide en cas d'erreur plutôt que de propager l'exception
            return [];
        }
    }

    /**
    * Récupère la liste des formations avec leurs années disponibles
    * @return array Tableau des formations avec leurs années
    */
    public function recupererFormationsAvecAnnees(): array {
        try {
            $formations = $this->dao->getFormationsAvecAnnees();
            $this->logLocal("[DEBUG] recupererFormationsAvecAnnees count=" . count($formations));
            return $formations;
        } catch (Exception $e) {
            $this->logLocal("[ERROR] recupererFormationsAvecAnnees:  " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère l'effectif total d'étudiants pour une formation et une année spécifiques.
     * Cette méthode est un simple "passe-plat" vers la méthode SQL optimisée du DAO.
     *
     * @param string $Formation Le nom de la formation.
     * @param int|string $Annee L'année scolaire.
     * @return int Le nombre total d'étudiants.
     */
    public function recupererEffectifParFormationAnnee($Formation, $Annee){
        try {
            return $this->dao->getEffectifTotalPrecise($Formation, $Annee);
        } catch (Exception $e) {
            $this->logLocal("[ERROR] recupererEffectifParFormationAnnee: " . $e->getMessage());
            return 0;
        }
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
    public function recupererRepartitionUEADMISParFormation($Formation, $Annee){
        $res = [];

        // Initialisation des clés pour garantir qu'elles existent, même avec une valeur de 0.
        $res['ue_6'] = 0;
        $res['ue_5'] = 0;
        $res['ue_4'] = 0;
        $res['ue_3'] = 0;
        $res['ue_2'] = 0;
        $res['ue_1'] = 0;

        try {
            // Récupération des données brutes de répartition du DAO (toutes années et parcours confondus)
            $list = $this->dao->getNbRepartitionUEADMISParFormation($Formation);

            // Parcours de toutes les lignes retournées par la BDD
            foreach($list as $ligne){
                
                // Si l'année de la ligne correspond à l'année demandée
                if((int)$ligne['annee_scolaire'] == (int)$Annee){
                    
                    // On utilise un switch pour affecter le nombre d'élèves (nb_eleves)
                    // à la bonne clé ('ue_X') dans le tableau de résultat.
                    switch($ligne['nb_ue_validees']){
                        case 6:
                            $res['ue_6'] += (int)$ligne['nb_eleves'];
                            break;

                        case 5:
                            $res['ue_5'] += (int)$ligne['nb_eleves'];
                            break;
                        
                        case 4:
                            $res['ue_4'] += (int)$ligne['nb_eleves'];
                            break;

                        case 3:
                            $res['ue_3'] += (int)$ligne['nb_eleves'];
                            break;

                        case 2:
                            $res['ue_2'] += (int)$ligne['nb_eleves'];
                            break;

                        case 1:
                            $res['ue_1'] += (int)$ligne['nb_eleves'];
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("[ERROR] recupererRepartitionUEADMISParFormation: " . $e->getMessage());
        }
        
        return $res;
    }

    /**
     * Récupère les données Sankey pour une cohorte depuis une année de départ
     * @param int $anneeDepart Année de départ de la cohorte
     * @param string $formation Code de la formation
     * @param string|null $parcours Parcours optionnel (non utilisé actuellement)
     * @return array Données formatées pour le diagramme Sankey
     */
    public function getSankeyCohorteDepuisAnnee(int $anneeDepart, string $formation, ?string $parcours = null): array {
        $annees = [$anneeDepart, $anneeDepart + 1, $anneeDepart + 2];

        $formatterPourJS = function(array $lignes): array {
            return array_map(function($ligne) {
                return [
                    'etudid' => (string)$ligne['etudid'],
                    'etat' => $ligne['etat'] ?? null,
                    'annee' => [
                        'ordre' => (int)$ligne['ordre'],
                        'code' => $ligne['code'],
                        'annee_scolaire' => (string)$ligne['annee_scolaire'],
                    ],
                ];
            }, $lignes);
        };

        $donnees = [];

        foreach ($annees as $annee) {
            try {
                $lignes = $this->dao->getCohorteParAnneeEtFormation($annee, $formation);
                $donnees[(string)$annee] = $formatterPourJS($lignes);
            } catch (Exception $e) {
                error_log("[ERROR] getSankeyCohorteDepuisAnnee année=$annee: " . $e->getMessage());
                // Continuer avec un tableau vide pour cette année
                $donnees[(string)$annee] = [];
            }
        }

        return [
            'annee_depart' => $anneeDepart,
            'annees' => $annees,
            'data' => $donnees
        ];
    }
}