<?php
require_once __DIR__ . '/../Models/StatsDAO.php';

/**
 * @package Service
 */
class Service_scodoc {
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

        public function getAllFormationAccronyme(){
                $rows = $this->dao->getallFormationByAccronyme();

                // Mapping simplifié des formations -> label et motifs de détection
                $mapping = [
                    'INFO' => ['label' => 'BUT Informatique', 'patterns' => ['INFO']],
                    'GEA'  => ['label' => 'BUT GEA', 'patterns' => ['GEA']],
                    'GEII' => ['label' => 'BUT GEII', 'patterns' => ['GEII','G_NIE','G_nie','GENIE']],
                    'RT'   => ['label' => 'BUT Réseaux et Télécommunications', 'patterns' => ['R&T','RT','R_T','RESEAU','TELE']],
                    'CJ'   => ['label' => 'BUT Carrières Juridiques', 'patterns' => ['CJ','CARRI'] ],
                    'SD'   => ['label' => 'BUT Science des Données', 'patterns' => ['SD','STID','DATA']],
                    'STID' => ['label' => 'BUT STID', 'patterns' => ['STID']],
                ];

                $found = [];

                foreach ($rows as $r) {
                    $raw = strtoupper($r['accronyme'] ?? ($r['titre'] ?? ''));

                    // Chercher une correspondance dans le mapping
                    foreach ($mapping as $code => $info) {
                        foreach ($info['patterns'] as $pat) {
                            if (strpos($raw, strtoupper($pat)) !== false) {
                                $found[$code] = ['accronyme' => $code, 'titre' => $info['label']];
                                break 2;
                            }
                        }
                    }
                }

                // Si aucun mapping reconnu, fallback : retourner les formations brutes
                if (empty($found)) {
                    return $rows;
                }

                // Retourner la liste réduite et indexée numériquement
                return array_values($found);

        }
           
        
}
?>