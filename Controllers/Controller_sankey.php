<?php
/**
 * Controller pour la visualisation Sankey des cohortes BUT
 */
class SankeyController {
    
    /**
     * Affiche la page du diagramme Sankey
     */
    public function index() {
        // Configuration des fichiers JSON à charger (chemins exacts)
        $config = [
            'files' => [
                '/Database/example/json/decisions_jury_2023_fs_1210_BUT_Informatique_en_FI_classique.json',
                '/Database/example/json/decisions_jury_2024_fs_1284_BUT_Informatique_en_FI_classique.json',
                '/Database/example/json/decisions_jury_2024_fs_1285_BUT_Informatique_en_FI_classique.json'
            ],
            'title' => 'Suivi de Cohorte d\'étudiants',
            'formation' => 'BUT Informatique'
        ];
        
        // Passer les données à la vue
        $this->render('sankey/index', $config);
    }
    
    /**
     * API pour récupérer les données d'une cohorte spécifique
     * Optionnel : si tu veux charger les données côté serveur
     */
    public function getCohorteData() {
        header('Content-Type: application/json');
        
        $year = $_GET['year'] ?? null;
        
        if (!$year || !in_array($year, ['2022', '2023', '2024'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Année invalide']);
            return;
        }
        
        $file = "/Database/example/json/decisions_jury_{$year}_fs_*.json";
        
        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Fichier introuvable']);
        }
    }
    
    /**
     * Méthode helper pour rendre une vue
     */
    private function render($view, $data = []) {
        extract($data);
        $viewFile = __DIR__ . "/../views/{$view}.php";
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new Exception("Vue introuvable : {$view}");
        }
    }
}