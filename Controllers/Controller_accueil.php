<?php
require_once __DIR__ . '/../Services/Service_api.php';

class HomeController {
    
    private Service_stats $service;
    
    public function __construct() {
        $this->service = new Service_stats();
    }
    
    /**
     * Affiche la page d'accueil avec le formulaire de sélection
     */
    public function index() {
        try {
            // Récupérer les formations et leurs années depuis la base de données
            $formations = $this->service->recupererFormationsAvecAnnees();
            
            // Passer les données à la vue
            require_once __DIR__ . '/../Views/view_accueil.php';
            
        } catch (Exception $e) {
            error_log('[ERREUR] HomeController->index :  ' . $e->getMessage());
            // En cas d'erreur, afficher quand même la vue avec un tableau vide
            $formations = [];
            require_once __DIR__ . '/../Views/view_accueil.php';
        }
    }
}