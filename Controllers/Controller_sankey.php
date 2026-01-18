<?php
require_once __DIR__ . '/../Services/Service_api.php';

/**
 * Controller pour la visualisation Sankey des cohortes BUT
 */
class Controller_sankey extends Controller {
    
    /**
     * @var Service_stats Instance du service pour accéder aux données
     */
    private Service_stats $service;
    
    /**
     * @var string Chemin vers le dossier des fichiers JSON de test
     */
    private string $jsonPath;
    
    /**
     * Constructeur - initialise le service et appelle le parent pour le routage
     */
    public function __construct() {
        $this->service = new Service_stats();
        $this->jsonPath = __DIR__ . '/../Database/example/json/';
        parent::__construct(); // Appel du parent pour gérer le routage des actions
    }
    
    /**
     * Action par défaut : affiche la page du diagramme Sankey
     */
    public function action_default() {
        // Récupérer les paramètres ou utiliser les valeurs par défaut
        $anneeDepart = isset($_GET['anneeDepart']) ? (int)substr(trim($_GET['anneeDepart']), 0, 4) : 2021;
        $formation = isset($_GET['formation']) ? trim($_GET['formation']) : 'INFO';
        // Option pour choisir la source de données : 'json' ou 'bdd' (par défaut 'json' car BDD non peuplée)
        $source = isset($_GET['source']) ? strtolower(trim($_GET['source'])) : 'json';
        
        $title = 'Suivi de Cohorte d\'étudiants';
        $formationLabel = 'BUT Informatique';
        
        // Récupérer les données selon la source choisie
        if ($source === 'json') {
            $sankeyData = $this->getSankeyDataFromJson($anneeDepart, $formation);
        } else {
            $sankeyData = $this->service->getSankeyCohorteDepuisAnnee($anneeDepart, $formation);
        }
        
        // Passer les données directement à la vue
        $this->render('sankey', [
            'sankeyData' => $sankeyData,
            'title' => $title,
            'formation' => $formationLabel,
            'anneeDepart' => $anneeDepart,
            'source' => $source
        ]);
    }
    
    /**
     * Charge les données Sankey depuis les fichiers JSON de test
     * 
     * @param int $anneeDepart Année de départ de la cohorte
     * @param string $formation Code de la formation (INFO, GEA, RT, etc.)
     * @return array Données formatées pour le diagramme Sankey
     */
    private function getSankeyDataFromJson(int $anneeDepart, string $formation): array {
        $annees = [$anneeDepart, $anneeDepart + 1, $anneeDepart + 2, $anneeDepart + 3];
        $donnees = [];
        
        // Mapping des codes de formation vers les patterns de fichiers
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
     * 
     * @param int $annee Année scolaire
     * @param string $pattern Pattern de la formation à rechercher
     * @return array Données des étudiants fusionnées
     */
    private function loadJsonFilesForYear(int $annee, string $pattern): array {
        $result = [];
        $files = glob($this->jsonPath . "decisions_jury_{$annee}_fs_*.json");
        
        foreach ($files as $file) {
            $filename = basename($file);
            // Vérifier si le fichier correspond à la formation recherchée
            if (preg_match("/BUT.*({$pattern})/i", $filename)) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                
                if (is_array($data)) {
                    // Déterminer l'ordre (BUT1, BUT2, BUT3) à partir du nom de fichier ou du contenu
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
    
    /**
     * Détermine l'ordre (1, 2 ou 3) du BUT à partir du nom de fichier
     * 
     * @param string $filename Nom du fichier
     * @return int Ordre du BUT (1, 2 ou 3)
     */
    private function determineOrdreFromFilename(string $filename): int {
        // Chercher des patterns comme BUT1, BUT_1, BUT 1, etc.
        if (preg_match('/BUT[_\s]?1/i', $filename)) return 1;
        if (preg_match('/BUT[_\s]?2/i', $filename)) return 2;
        if (preg_match('/BUT[_\s]?3/i', $filename)) return 3;
        
        // Chercher S1/S2 pour BUT1, S3/S4 pour BUT2, S5/S6 pour BUT3
        if (preg_match('/S[12][^0-9]/i', $filename)) return 1;
        if (preg_match('/S[34][^0-9]/i', $filename)) return 2;
        if (preg_match('/S[56][^0-9]/i', $filename)) return 3;
        
        // Par défaut, retourner 1
        return 1;
    }
    
    /**
     * Détermine le code de décision d'un étudiant à partir de ses données
     * 
     * @param array $etudiant Données de l'étudiant
     * @return string Code de décision (ADM, AJ, RED, etc.)
     */
    private function determineCodeFromEtudiant(array $etudiant): string {
        // Si l'étudiant a un code directement
        if (isset($etudiant['code'])) {
            return $etudiant['code'];
        }
        
        // Analyser l'état de l'étudiant
        $etat = $etudiant['etat'] ?? '';
        
        // Mapping des états vers les codes
        $etatToCode = [
            'I' => 'AJ',      // Inscrit mais pas de décision = ajourné par défaut
            'D' => 'DEM',     // Démission
            'DEF' => 'DEF',   // Défaillant
        ];
        
        if (isset($etatToCode[$etat])) {
            return $etatToCode[$etat];
        }
        
        // Analyser les RCUEs pour déterminer le code global
        if (isset($etudiant['rcues']) && is_array($etudiant['rcues'])) {
            $allAdm = true;
            $hasAj = false;
            
            foreach ($etudiant['rcues'] as $rcue) {
                $code = $rcue['code'] ?? 'AJ';
                if ($code !== 'ADM' && $code !== 'CMP') {
                    $allAdm = false;
                }
                if ($code === 'AJ') {
                    $hasAj = true;
                }
            }
            
            if ($allAdm) return 'ADM';
            if ($hasAj) return 'AJ';
        }
        
        // Par défaut
        return 'AJ';
    }
    
    /**
     * API pour récupérer les données d'une cohorte spécifique (JSON)
     */
    public function action_getCohorteData() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if (!isset($_GET['anneeDepart']) || trim($_GET['anneeDepart']) === '') {
                throw new InvalidArgumentException("Paramètre 'anneeDepart' obligatoire.");
            }
            if (!isset($_GET['formation']) || trim($_GET['formation']) === '') {
                throw new InvalidArgumentException("Paramètre 'formation' obligatoire.");
            }
            
            $annee = (int)substr(trim($_GET['anneeDepart']), 0, 4);
            $formation = trim($_GET['formation']);
            // Option pour choisir la source : 'json' ou 'bdd'
            $source = isset($_GET['source']) ? strtolower(trim($_GET['source'])) : 'json';
            
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
    }
    
    /**
     * Liste les formations disponibles dans les fichiers JSON
     */
    public function action_getFormations() {
        header('Content-Type: application/json; charset=utf-8');
        
        $formations = [
            ['code' => 'INFO', 'label' => 'BUT Informatique'],
            ['code' => 'GEA', 'label' => 'BUT GEA'],
            ['code' => 'RT', 'label' => 'BUT Réseaux et Télécommunications'],
            ['code' => 'GEII', 'label' => 'BUT GEII'],
            ['code' => 'CJ', 'label' => 'BUT Carrières Juridiques'],
            ['code' => 'SD', 'label' => 'BUT Science des Données'],
        ];
        
        echo json_encode($formations, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Liste les années disponibles dans les fichiers JSON
     */
    public function action_getAnnees() {
        header('Content-Type: application/json; charset=utf-8');
        
        $files = glob($this->jsonPath . 'decisions_jury_*_fs_*.json');
        $annees = [];
        
        foreach ($files as $file) {
            if (preg_match('/decisions_jury_(\d{4})_fs_/', basename($file), $matches)) {
                $annees[] = (int)$matches[1];
            }
        }
        
        $annees = array_unique($annees);
        sort($annees);
        
        echo json_encode($annees, JSON_UNESCAPED_UNICODE);
    }
}