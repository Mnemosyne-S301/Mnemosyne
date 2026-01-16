<?php
require_once __DIR__ . '/../Services/Service_filtres.php';

class Controller_api extends Controller {

    /* ajouter les autres méthodes ici sous la forme action_quelquechose */
    
    public function action_default() {
        /* Méthode abstraite à reimplementer sinon ça marchera pas */
    }

    /**
     * Récupère un paramètre depuis POST ou GET
     * 
     * @param string $name Nom du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed La valeur du paramètre ou la valeur par défaut
     */
    private function getParameter($name, $default = null) {
        return $_POST[$name] ?? $_GET[$name] ?? $default;
    }

    /**
     * Endpoint API pour appliquer des filtres sur les étudiants
     * Méthode POST attendue avec les paramètres :
     * - formation (string) : nom de la formation
     * - annee (int) : année scolaire
     * - critere (string) : "en formation", "ayant plus de", "ayant moins de"
     * - seuil (int, optionnel) : seuil d'UE pour les critères "ayant plus de" et "ayant moins de"
     * - statut (string) : "réussite" ou "échec"
     */
    public function action_appliquer_filtre() {
        header('Content-Type: application/json');
        
        $service = new Service_filtres();
        
        // Récupérer les paramètres de la requête
        $formation = $this->getParameter('formation', '');
        $annee = $this->getParameter('annee', '');
        $critere = $this->getParameter('critere', '');
        $seuilRaw = $this->getParameter('seuil');
        $statut = $this->getParameter('statut', 'réussite');
        
        // Valider et convertir le seuil
        $seuil = null;
        if ($seuilRaw !== null) {
            if (!is_numeric($seuilRaw)) {
                echo json_encode([
                    'success' => false,
                    'errors' => ['Le seuil doit être un nombre valide']
                ]);
                return;
            }
            $seuil = (int)$seuilRaw;
        }
        
        $params = [
            'formation' => $formation,
            'annee' => $annee,
            'critere' => $critere,
            'seuil' => $seuil,
            'statut' => $statut
        ];
        
        // Valider les paramètres
        $validation = $service->validerParametresFiltre($params);
        
        if (!$validation['valid']) {
            echo json_encode([
                'success' => false,
                'errors' => $validation['errors']
            ]);
            return;
        }
        
        // Appliquer le filtre
        try {
            $resultats = $service->appliquerFiltre(
                $params['formation'],
                $params['annee'],
                $params['critere'],
                $params['seuil'],
                $params['statut']
            );
            
            $formatted = $service->formaterResultats($resultats);
            
            echo json_encode([
                'success' => true,
                'data' => $formatted,
                'raw' => $resultats
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'errors' => ['Une erreur est survenue : ' . $e->getMessage()]
            ]);
        }
    }

    /**
     * Endpoint API pour compter les étudiants filtrés
     * Méthode GET/POST avec les mêmes paramètres que action_appliquer_filtre
     */
    public function action_compter_filtres() {
        header('Content-Type: application/json');
        
        $service = new Service_filtres();
        
        // Récupérer les paramètres de la requête
        $formation = $this->getParameter('formation', '');
        $annee = $this->getParameter('annee', '');
        $critere = $this->getParameter('critere', '');
        $seuilRaw = $this->getParameter('seuil');
        $statut = $this->getParameter('statut', 'réussite');
        
        // Valider et convertir le seuil
        $seuil = null;
        if ($seuilRaw !== null) {
            if (!is_numeric($seuilRaw)) {
                echo json_encode([
                    'success' => false,
                    'errors' => ['Le seuil doit être un nombre valide']
                ]);
                return;
            }
            $seuil = (int)$seuilRaw;
        }
        
        $params = [
            'formation' => $formation,
            'annee' => $annee,
            'critere' => $critere,
            'seuil' => $seuil,
            'statut' => $statut
        ];
        
        $validation = $service->validerParametresFiltre($params);
        
        if (!$validation['valid']) {
            echo json_encode([
                'success' => false,
                'errors' => $validation['errors']
            ]);
            return;
        }
        
        try {
            $count = $service->compterEtudiantsFiltres(
                $params['formation'],
                $params['annee'],
                $params['critere'],
                $params['seuil'],
                $params['statut']
            );
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'errors' => ['Une erreur est survenue : ' . $e->getMessage()]
            ]);
        }
    }

}
?>