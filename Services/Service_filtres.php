<?php
require_once __DIR__ . '/../Models/StatsDAO.php';

/**
 * Classe Service_filtres.
 * Rôle : Couche de logique métier pour gérer les filtres sur les données étudiants.
 * Permet de filtrer les étudiants selon différents critères (formation, nombre d'UE validées, statut).
 */
class Service_filtres {
    
    /**
     * @var StatsDAO $dao Instance du Data Access Object pour interagir avec la BDD.
     */
    private StatsDAO $dao;

    /**
     * Constructeur.
     * Initialise le Service en récupérant l'unique instance du StatsDAO (Singleton).
     */
    public function __construct() {
        $this->dao = StatsDAO::getModel();
    }

    /**
     * Applique un filtre sur les étudiants en fonction des critères fournis.
     * 
     * @param string $formation Le nom de la formation
     * @param int $annee L'année scolaire
     * @param string $critere Le type de critère ("en formation", "ayant plus de", "ayant moins de")
     * @param int|null $seuil Le seuil d'UE validées (pour "ayant plus de" ou "ayant moins de")
     * @param string $statut Le statut recherché ("réussite" ou "échec")
     * @return array Tableau des étudiants filtrés
     */
    public function appliquerFiltre($formation, $annee, $critere, $seuil = null, $statut = "réussite") {
        $resultats = [];
        
        // Récupérer les données de répartition des UE pour la formation et l'année
        $repartition = $this->dao->getNbRepartitionUEADMISParFormation($formation);
        
        foreach ($repartition as $ligne) {
            // Vérifier que l'année correspond
            if ((int)$ligne['annee_scolaire'] !== (int)$annee) {
                continue;
            }
            
            $nbUE = (int)$ligne['nb_ue_validees'];
            $nbEleves = (int)$ligne['nb_eleves'];
            
            // Appliquer le critère de filtrage
            $inclure = false;
            
            switch ($critere) {
                case "en formation":
                    // Tous les étudiants de la formation
                    $inclure = true;
                    break;
                    
                case "ayant plus de":
                    if ($seuil !== null && $nbUE > $seuil) {
                        $inclure = true;
                    }
                    break;
                    
                case "ayant moins de":
                    if ($seuil !== null && $nbUE < $seuil) {
                        $inclure = true;
                    }
                    break;
            }
            
            // Appliquer le filtre de statut (réussite/échec)
            // Considérons que 4+ UE validées = réussite, <4 = échec (à adapter selon les règles métier)
            $seuilReussite = 4;
            if ($statut === "réussite" && $nbUE < $seuilReussite) {
                $inclure = false;
            } elseif ($statut === "échec" && $nbUE >= $seuilReussite) {
                $inclure = false;
            }
            
            if ($inclure) {
                $resultats[] = [
                    'nb_ue_validees' => $nbUE,
                    'nb_eleves' => $nbEleves,
                    'parcours' => $ligne['parcours'] ?? '',
                    'formation' => $formation,
                    'annee_scolaire' => $ligne['annee_scolaire']
                ];
            }
        }
        
        return $resultats;
    }

    /**
     * Récupère le nombre total d'étudiants correspondant à un filtre.
     * 
     * @param string $formation Le nom de la formation
     * @param int $annee L'année scolaire
     * @param string $critere Le type de critère
     * @param int|null $seuil Le seuil d'UE validées
     * @param string $statut Le statut recherché
     * @return int Le nombre total d'étudiants
     */
    public function compterEtudiantsFiltres($formation, $annee, $critere, $seuil = null, $statut = "réussite") {
        $resultats = $this->appliquerFiltre($formation, $annee, $critere, $seuil, $statut);
        $total = 0;
        
        foreach ($resultats as $ligne) {
            $total += $ligne['nb_eleves'];
        }
        
        return $total;
    }

    /**
     * Valide les paramètres d'un filtre.
     * 
     * @param array $params Tableau associatif contenant les paramètres du filtre
     * @return array Tableau avec 'valid' (bool) et 'errors' (array)
     */
    public function validerParametresFiltre($params) {
        $errors = [];
        
        if (empty($params['formation'])) {
            $errors[] = "La formation est requise";
        }
        
        if (empty($params['annee'])) {
            $errors[] = "L'année scolaire est requise";
        }
        
        if (empty($params['critere'])) {
            $errors[] = "Le critère est requis";
        } elseif (!in_array($params['critere'], ["en formation", "ayant plus de", "ayant moins de"])) {
            $errors[] = "Critère invalide";
        }
        
        if (in_array($params['critere'], ["ayant plus de", "ayant moins de"])) {
            if (!isset($params['seuil']) || !is_numeric($params['seuil'])) {
                $errors[] = "Le seuil est requis et doit être un nombre";
            }
        }
        
        if (!empty($params['statut']) && !in_array($params['statut'], ["réussite", "échec"])) {
            $errors[] = "Statut invalide";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Formate les résultats du filtre pour l'affichage.
     * 
     * @param array $resultats Les résultats bruts du filtre
     * @return array Tableau formaté pour l'affichage
     */
    public function formaterResultats($resultats) {
        $formatted = [
            'total_etudiants' => 0,
            'details' => [],
            'resume' => []
        ];
        
        foreach ($resultats as $ligne) {
            $formatted['total_etudiants'] += $ligne['nb_eleves'];
            $formatted['details'][] = [
                'nb_ue' => $ligne['nb_ue_validees'],
                'effectif' => $ligne['nb_eleves'],
                'parcours' => $ligne['parcours']
            ];
        }
        
        // Créer un résumé
        $formatted['resume'] = [
            'nombre_groupes' => count($resultats),
            'total' => $formatted['total_etudiants']
        ];
        
        return $formatted;
    }
}
?>