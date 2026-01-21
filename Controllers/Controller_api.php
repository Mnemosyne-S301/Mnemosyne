<?php
require_once __DIR__ . "/../Services/Service_api.php";

/**
 * Controller API pour fournir les données en JSON
 * Utilisé par les appels fetch() depuis le JavaScript
 * 
 * @package Controller
 */
class Controller_api extends Controller {
        /**
         * Détermine l'ordre (BUT1, BUT2, BUT3) à partir du nom de fichier
         */
        private function determineOrdreFromFilename(string $filename): int {
            if (preg_match('/BUT[_\s]?1/i', $filename)) return 1;
            if (preg_match('/BUT[_\s]?2/i', $filename)) return 2;
            if (preg_match('/BUT[_\s]?3/i', $filename)) return 3;
            if (preg_match('/S[12][^0-9]/i', $filename)) return 1;
            if (preg_match('/S[34][^0-9]/i', $filename)) return 2;
            if (preg_match('/S[56][^0-9]/i', $filename)) return 3;
            return 1;
        }

        /**
         * Détermine le code de décision d'un étudiant à partir de ses données
         * Retourne null si l'étudiant n'a pas de décision annuelle (annee: [])
         */
        private function determineCodeFromEtudiant(array $etudiant): ?string {
            // Si annee est un tableau vide, pas de décision annuelle
            if (isset($etudiant['annee']) && is_array($etudiant['annee']) && empty($etudiant['annee'])) {
                return null;
            }
            if (isset($etudiant['annee']['code'])) {
                return $etudiant['annee']['code'];
            }
            if (isset($etudiant['decisions']['annee']['code'])) {
                return $etudiant['decisions']['annee']['code'];
            }
            if (isset($etudiant['code'])) {
                return $etudiant['code'];
            }
            return null;
        }
    
    private Service_stats $service;
    private string $jsonPath;

    public function __construct() {
        $this->service = new Service_stats();
        $this->jsonPath = __DIR__ . '/../Database/example/json/';
        
        // Définir le header JSON avant tout
        header('Content-Type: application/json; charset=utf-8');
        
        parent::__construct();
    }

    /**
     * Action par défaut - retourne une erreur
     */
    public function action_default() {
        http_response_code(400);
        echo json_encode(['error' => 'Action non spécifiée. Utilisez ?action=sankey'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Action sankey - retourne les données pour le diagramme Sankey
     * Paramètres GET : anneeDepart, formation, source (json|bdd)
     */
    public function action_sankey() {
        try {
            // Validation des paramètres
            if (!isset($_GET['anneeDepart']) || trim($_GET['anneeDepart']) === '') {
                throw new InvalidArgumentException("Paramètre 'anneeDepart' obligatoire.");
            }
            if (!isset($_GET['formation']) || trim($_GET['formation']) === '') {
                throw new InvalidArgumentException("Paramètre 'formation' obligatoire.");
            }

            $annee = (int)substr(trim($_GET['anneeDepart']), 0, 4);
            $formation = strtoupper(trim($_GET['formation']));
            $source = isset($_GET['source']) ? strtolower(trim($_GET['source'])) : 'json';

            // Récupérer les données selon la source
            if ($source === 'json') {
                $donnees = $this->getSankeyDataFromJson($annee, $formation);
            } elseif ($source === 'testdata') {
                $donnees = $this->getSankeyDataFromJson($annee, $formation, 'testdata');
            } else {
                $donnees = $this->service->getSankeyCohorteDepuisAnnee($annee, $formation);
            }

            http_response_code(200);
            echo json_encode($donnees, JSON_UNESCAPED_UNICODE);

        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Action stats - retourne les statistiques de cohorte (effectif, diplomes, abandons, en cours)
     * Paramètres GET : anneeDepart, formation
     */
    public function action_stats() {
            try {
                if (!isset($_GET['anneeDepart']) || trim($_GET['anneeDepart']) === '') {
                    throw new InvalidArgumentException("Paramètre 'anneeDepart' obligatoire.");
                }
                if (!isset($_GET['formation']) || trim($_GET['formation']) === '') {
                    throw new InvalidArgumentException("Paramètre 'formation' obligatoire.");
                }

                $annee = (int)substr(trim($_GET['anneeDepart']), 0, 4);
                $formation = strtoupper(trim($_GET['formation']));

                // Récupérer les données Sankey pour calculer les stats à partir des codes de décision
                $sankeyData = $this->service->getSankeyCohorteDepuisAnnee($annee, $formation);
                
                // Codes de décision pour classifier les étudiants
                $codesValidation = ['ADM', 'ADSUP', 'PASD', 'CMP'];
                $codesAbandon = ['NAR', 'DEM', 'DEF'];
                $codesEnCours = ['RED', 'AJ', 'ADJ'];
                
                // Compteurs
                $etudiantsVus = [];
                $diplomes = 0;
                $abandons = 0;
                $encours = 0;
                
                // Parcourir toutes les années pour trouver le dernier état de chaque étudiant
                $dernierEtat = [];
                $annees = $sankeyData['annees'] ?? [];
                foreach ($annees as $an) {
                    $dataAnnee = $sankeyData['data'][(string)$an] ?? [];
                    foreach ($dataAnnee as $etud) {
                        $etudid = $etud['etudid'];
                        $code = $etud['annee']['code'] ?? '';
                        $ordre = $etud['annee']['ordre'] ?? 1;
                        // Garder le dernier état connu (année la plus récente)
                        if (!isset($dernierEtat[$etudid]) || $an > $dernierEtat[$etudid]['annee']) {
                            $dernierEtat[$etudid] = ['code' => $code, 'ordre' => $ordre, 'annee' => $an];
                        }
                    }
                }
                
                // Classifier chaque étudiant selon son dernier code
                foreach ($dernierEtat as $etudid => $info) {
                    $code = $info['code'];
                    $ordre = $info['ordre'];
                    
                    if (in_array($code, $codesAbandon)) {
                        $abandons++;
                    } elseif (in_array($code, $codesValidation) && $ordre >= 3) {
                        // Diplômé = validé en BUT3
                        $diplomes++;
                    } else {
                        // Tous les autres sont "en cours"
                        $encours++;
                    }
                }
                
                $effectif = count($dernierEtat);
                
                // Calculer le taux de réussite (diplômés / effectif total)
                $tauxReussite = $effectif > 0 ? round(($diplomes / $effectif) * 100, 1) : 0;
                $tauxAbandon = $effectif > 0 ? round(($abandons / $effectif) * 100, 1) : 0;
                
                // Essayer de récupérer les stats détaillées depuis la BDD stats
                $statsDetaillees = null;
                $repartitionUE = [];
                $tauxValidation6UE = null;
                $moyenneUE = null;
                
                try {
                    require_once __DIR__ . '/../Models/StatsDAO.php';
                    $statsDao = StatsDAO::getModel();
                    
                    // Mapping des codes courts vers les noms de formation en BDD stats
                    $formationMapping = [
                        'INFO' => 'Informatique',
                        'GEA' => 'GEA',
                        'RT' => 'R&T',
                        'GEII' => 'GEII',
                        'CJ' => 'Carrières Juridiques',
                        'SD' => 'SD'
                    ];
                    $formationName = $formationMapping[$formation] ?? $formation;
                    
                    $statsDetaillees = $statsDao->getStatsDetailleesFormation($formationName, $annee);
                    if ($statsDetaillees) {
                        $repartitionUE = $statsDetaillees['repartition_ue'] ?? [];
                        $tauxValidation6UE = $statsDetaillees['taux_validation_6ue'] ?? null;
                        $moyenneUE = $statsDetaillees['moyenne_ue_validees'] ?? null;
                    }
                } catch (Throwable $e) {
                    // Si erreur sur la BDD stats, on continue sans ces données
                    error_log('Erreur stats BDD: ' . $e->getMessage());
                }
                
                $stats = [
                    'effectif' => $effectif,
                    'diplomes' => $diplomes,
                    'abandons' => $abandons,
                    'encours' => $encours,
                    'tauxReussite' => $tauxReussite,
                    'tauxAbandon' => $tauxAbandon,
                    'repartitionUE' => $repartitionUE,
                    'tauxValidation6UE' => $tauxValidation6UE,
                    'moyenneUE' => $moyenneUE
                ];
                http_response_code(200);
                echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

    /**
     * Charge les données Sankey depuis les fichiers JSON de test
     */
    private function getSankeyDataFromJson(int $anneeDepart, string $formation, string $jsonSource = 'json'): array {
        $annees = [$anneeDepart, $anneeDepart + 1, $anneeDepart + 2, $anneeDepart + 3];
        $formationPatterns = [
            'INFO' => 'Informatique|INFO|BUT_Informatique_en_FI_classique|BUT_Informatique_en_alternance|BUT_informatique_en_alternance|BUT_Informatique_en_FA_alternance',
            'GEA' => 'GEA|BUT_GEA|BUT1_GEA|BUT2_GEA|BUT3_GEA|BUT_GEA_Apprentissage|BUT_GEA_en_Apprentissage|BUT_GEA_FI_S4|BUT_GEA_FI|BUT_GEA_FA',
            'RT' => 'R_T|BUT_R_T|BUT_R_T_en_alternance|BUT_R_T_en_altenance',
            'GEII' => 'G_nie_Electrique_et_Informatique_Industrielle|GEII|BUT_GEII|BUT_GEII_FA',
            'CJ' => 'Carri_res_Juridiques|CJ|Bachelor_Universitaire_de_Technologie_Carri_res_Juridiques|BUT_Carri_res_Juridiques|BUT_Carri_res_Juridiques_-_Parcours_AJ|BUT_Carri_res_Juridiques_-_Parcours_EA|BUT_Carri_res_Juridiques_-_Parcours_PF_Banque_|BUT_Carri_res_Juridiques_-_Parcours_PF_Notariat_|BUT_Carri_res_Juridiques_-_Parcours_EA_FA_|BUT_Carri_res_Juridiques_-_Parcours_AJ_EA_PF|BUT_CJ_-_Parcours_AJ_EA_PF_Formation_initiale_|BUT_Carri_res_Juridiques_-_Parcours_PF|BUT_Carri_res_Juridiques_-Parcours_EA_FA_',
            'SD' => 'SD|STID|BUT_SD_PN_2021_',
            'PASS' => 'BUT_Passerelle_SD_INFO',
        ];
        $pattern = $formationPatterns[$formation] ?? $formation;

        // Déterminer le chemin du dossier JSON selon la source
        if ($jsonSource === 'testdata') {
            $jsonPath = __DIR__ . '/../Database/example/json/testdata/';
            $filePattern = 'test_promo_' . $anneeDepart . '_v*.json';
        } else {
            $jsonPath = __DIR__ . '/../Database/example/json/';
            $filePattern = 'decisions_jury_' . $anneeDepart . '_fs_*.json';
        }

        // Chargement et déduplication par etudid sur toutes les années
        $allData = [];
        foreach ($annees as $annee) {
            if ($jsonSource === 'testdata') {
                $allData[(string)$annee] = $this->loadJsonFilesForYear($annee, $pattern, $jsonPath, 'testdata');
            } else {
                $allData[(string)$annee] = $this->loadJsonFilesForYear($annee, $pattern, $jsonPath, 'json');
            }
        }

        // Déduplication par etudid (on garde la première occurrence par année)
        foreach ($allData as $annee => &$etudiants) {
            $seen = [];
            $dedup = [];
            foreach ($etudiants as $etudiant) {
                $id = $etudiant['etudid'];
                if (!isset($seen[$id])) {
                    $dedup[] = $etudiant;
                    $seen[$id] = true;
                }
            }
            $etudiants = $dedup;
        }
        unset($etudiants);

        // Construction du format attendu par le JS : data2021, data2022, ...
        $dataByYear = [];
        foreach ($allData as $annee => $etudiants) {
            $dataByYear['data' . $annee] = $etudiants;
        }

        // Normalisation des codes d'état
        $normalizeCode = function($code) {
            $map = [
                'ADM' => 'ADM', 'ADMI' => 'ADM', 'AD' => 'ADM',
                'RED' => 'RED', 'RE' => 'RED',
                'AJ' => 'AJ', 'AJA' => 'AJ',
                'ATT' => 'ATT', 'ATTENTE' => 'ATT',
                'ABS' => 'ABS',
                'DEC' => 'DEC',
                'DES' => 'DES',
                'EXC' => 'EXC',
                'ABAN' => 'ABAN', 'ABANDON' => 'ABAN',
            ];
            $uc = strtoupper($code);
            return $map[$uc] ?? $uc;
        };
        foreach ($allData as $annee => &$etudiants) {
            foreach ($etudiants as &$etudiant) {
                $etudiant['annee']['code'] = $normalizeCode($etudiant['annee']['code']);
            }
        }
        unset($etudiant);

        // Calcul des transitions pour Sankey (source/target)
        $links = [];
        $nodes = [];
        $nodeIndex = [];
        // On crée un identifiant unique pour chaque état/année
        foreach ($annees as $i => $annee) {
            foreach ($allData[(string)$annee] as $etudiant) {
                $label = $annee . ' ' . $etudiant['annee']['code'];
                if (!isset($nodeIndex[$label])) {
                    $nodeIndex[$label] = count($nodes);
                    $nodes[] = $label;
                }
            }
        }
        // Pour chaque étudiant, on relie son état d'une année à l'état de l'année suivante
        for ($i = 0; $i < count($annees) - 1; $i++) {
            $an1 = (string)$annees[$i];
            $an2 = (string)$annees[$i+1];
            $byId = [];
            foreach ($allData[$an1] as $etudiant) {
                $byId[$etudiant['etudid']] = $etudiant['annee']['code'];
            }
            foreach ($allData[$an2] as $etudiant) {
                $id = $etudiant['etudid'];
                if (isset($byId[$id])) {
                    $from = $an1 . ' ' . $byId[$id];
                    $to = $an2 . ' ' . $etudiant['annee']['code'];
                    $links[] = [
                        'source' => $nodeIndex[$from],
                        'target' => $nodeIndex[$to],
                        'value' => 1
                    ];
                }
            }
        }

        return array_merge([
            'annee_depart' => $anneeDepart,
            'annees' => $annees,
            'nodes' => $nodes,
            'links' => $links,
            'data' => $allData
        ], $dataByYear);
    }

    /**
     * Charge tous les fichiers JSON correspondant à une année et une formation
     */
    private function loadJsonFilesForYear(int $annee, string $pattern, string $jsonPath = null): array {
        $result = [];
        $jsonPath = $jsonPath ?? $this->jsonPath;
        $args = func_get_args();
        $mode = $args[3] ?? 'json';
        if ($mode === 'testdata') {
            $files = glob($jsonPath . "test_promo_{$annee}_v*.json");
        } else {
            $files = glob($jsonPath . "decisions_jury_{$annee}_fs_*.json");
        }
        foreach ($files as $file) {
            $filename = basename($file);
            if ($mode === 'testdata' || preg_match("/BUT.*({$pattern})/i", $filename)) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if (is_array($data)) {
                    foreach ($data as $etudiant) {
                        if (isset($etudiant['etudid'])) {
                            // Ignorer les étudiants sans décision annuelle (annee: [])
                            $code = $this->determineCodeFromEtudiant($etudiant);
                            if ($code === null) {
                                continue;
                            }
                            // Priorité à l'ordre du JSON si présent, sinon heuristique sur le nom de fichier
                            $ordre = isset($etudiant['annee']['ordre']) ? (int)$etudiant['annee']['ordre'] : $this->determineOrdreFromFilename($filename);
                            $result[] = [
                                'etudid' => (string)$etudiant['etudid'],
                                'etat' => $etudiant['etat'] ?? null,
                                'annee' => [
                                    'ordre' => $ordre,
                                    'code' => $code,
                                    'annee_scolaire' => (string)$annee,
                                ],
                            ];
                        }
                    }
                }
            }
        }
        return $result;
    }
}