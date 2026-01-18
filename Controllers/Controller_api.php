<?php
require_once __DIR__ . "/../Services/Service_api.php";

/**
 * Controller API pour fournir les données en JSON
 * Utilisé par les appels fetch() depuis le JavaScript
 */
class Controller_api extends Controller {
    
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
     * Charge les données Sankey depuis les fichiers JSON de test
     */
    private function getSankeyDataFromJson(int $anneeDepart, string $formation): array {
        $annees = [$anneeDepart, $anneeDepart + 1, $anneeDepart + 2, $anneeDepart + 3];
        $donnees = [];
        
        $formationPatterns = [
            'INFO' => 'Informatique',
            'GEA' => 'GEA',
            'RT' => 'R_T',
            'GEII' => 'G_nie_Electrique|GEII',
            'CJ' => 'Carri_res_Juridiques|CJ',
            'SD' => 'SD|STID',
        ];
        
        $pattern = $formationPatterns[$formation] ?? $formation;
        
        foreach ($annees as $annee) {
            $donnees[(string)$annee] = $this->loadJsonFilesForYear($annee, $pattern);
        }
        
        return [
            'annee_depart' => $anneeDepart,
            'annees' => $annees,
            'data' => $donnees
        ];
    }

    /**
     * Charge tous les fichiers JSON correspondant à une année et une formation
     */
    private function loadJsonFilesForYear(int $annee, string $pattern): array {
        $result = [];
        $files = glob($this->jsonPath . "decisions_jury_{$annee}_fs_*.json");
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match("/BUT.*({$pattern})/i", $filename)) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                
                if (is_array($data)) {
                    $ordre = $this->determineOrdreFromFilename($filename);
                    
                    foreach ($data as $etudiant) {
                        if (isset($etudiant['etudid'])) {
                            $result[] = [
                                'etudid' => (string)$etudiant['etudid'],
                                'etat' => $etudiant['etat'] ?? null,
                                'annee' => [
                                    'ordre' => $ordre,
                                    'code' => $this->determineCodeFromEtudiant($etudiant),
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

    private function determineOrdreFromFilename(string $filename): int {
        if (preg_match('/BUT[_\s]?1/i', $filename)) return 1;
        if (preg_match('/BUT[_\s]?2/i', $filename)) return 2;
        if (preg_match('/BUT[_\s]?3/i', $filename)) return 3;
        if (preg_match('/S[12][^0-9]/i', $filename)) return 1;
        if (preg_match('/S[34][^0-9]/i', $filename)) return 2;
        if (preg_match('/S[56][^0-9]/i', $filename)) return 3;
        return 1;
    }

    private function determineCodeFromEtudiant(array $etudiant): string {
        if (isset($etudiant['annee']['code'])) {
            return $etudiant['annee']['code'];
        }
        if (isset($etudiant['decisions']) && isset($etudiant['decisions']['annee'])) {
            return $etudiant['decisions']['annee']['code'] ?? 'ATT';
        }
        if (isset($etudiant['code'])) {
            return $etudiant['code'];
        }
        return 'ATT';
    }
}



