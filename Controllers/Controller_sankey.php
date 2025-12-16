<?php
/**
 * Controller pour la visualisation Sankey des cohortes BUT
 */
class Controller_sankey extends Controller {
    
    /**
     * Action par défaut : affiche la page du diagramme Sankey
     */
    public function action_default() {
        // Charger les fichiers JSON directement depuis le serveur
        $data2021 = $this->loadJsonFile('Database/example/json/testdata/test_promo_2021_v2.json');
        $data2022 = $this->loadJsonFile('Database/example/json/testdata/test_promo_2022_v2.json');
        $data2023 = $this->loadJsonFile('Database/example/json/testdata/test_promo_2023_v2.json');
        
        $title = 'Suivi de Cohorte d\'étudiants';
        $formation = 'BUT Informatique';
        
        // Passer les données directement à la vue
        $this->render('sankey', [
            'data2021' => $data2021,
            'data2022' => $data2022,
            'data2023' => $data2023,
            'title' => $title,
            'formation' => $formation
        ]);
    }
    
    /**
     * Charger et parser un fichier JSON
     */
    private function loadJsonFile($filePath) {
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), true);
        }
        return [];
    }
    
    /**
     * API pour récupérer les données d'une cohorte spécifique
     */
    public function action_getCohorteData() {
        header('Content-Type: application/json');
        
        $year = $_GET['year'] ?? null;
        
        if (!$year || !in_array($year, ['2021', '2022', '2023'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Année invalide']);
            return;
        }
        
        $file = "/Database/example/json/testdata/test_promo_{$year}_v2.json";
        
        if (file_exists($file)) {
            echo file_get_contents($file);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Fichier introuvable']);
        }
    }
}