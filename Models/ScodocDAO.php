<?php 
require_once __DIR__. "/Etudiant.php";
require_once __DIR__. "/Formation.php";
require_once __DIR__. "/Departement.php";
require_once __DIR__. "/UE.php";
require_once __DIR__. "/RCUE.php"; 
require_once __DIR__. "/Formsemestre.php";
require_once __DIR__. "/Decision.php";


/**
 * Le DAO permettant de recuperer les données depuis l'API ScoDoc
 * @package DAO
 */    
class ScodocDAO
{
    private string $apiUrl;
    private ?string $token;

    public function __construct(string $apiUrl, ?string $token = null)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->token = $token !== null ? trim($token) : null;
    }

    /**
     * Exécute une requête GET vers l'API ScoDoc.
     */
    private function get(string $path): array
    {
        if (empty($this->token)) {
            throw new RuntimeException("Token ScoDoc manquant.");
        }

        $url = $this->apiUrl . '/' . ltrim($path, '/');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Erreur cURL : $curlError");
        }

        if ($httpCode >= 400) {
            throw new RuntimeException("Erreur HTTP $httpCode sur $url : $response");
        }

        $decoded = json_decode($response, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Réponse JSON invalide : " . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Vérifie si une formation correspond à un BUT.
     */
    private function isButFormation(array $formation): bool
    {
        return (
            (isset($formation['type_titre']) && strtoupper((string)$formation['type_titre']) === 'BUT')
            || (isset($formation['titre']) && stripos((string)$formation['titre'], 'BUT') === 0)
        );
    }

    public function getDepartements(): array
    {
        return $this->get('/departements');
    }

    public function getFormations(): array
    {
        return $this->get('/formations');
    }

    public function getReferentielCompetences(int $formationId): array
    {
        return $this->get('/formation/' . $formationId . '/referentiel_competences');
    }

    public function getFormsemestresByAnnee(int $anneeScolaire): array
    {
        return $this->get('/formsemestres/query?annee_scolaire=' . $anneeScolaire);
    }

    public function getDecisionsJury(int $formsemestreId): array
    {
        return $this->get('/formsemestre/' . $formsemestreId . '/decisions_jury');
    }

    /**
     * Récupère les données nécessaires aux cohortes :
     * départements, formations BUT, référentiels, semestres et décisions jury.
     */
    public function recupererDonneesCohortes(array $annees = [2021, 2022, 2023, 2024]): array
    {
        $resultat = [
            'departements' => [],
            'formations' => [],
            'formations_but' => [],
            'referentiels_competences' => [],
            'formsemestres' => [],
            'formsemestres_but' => [],
            'decisions_jury' => [],
            'erreurs' => []
        ];

        try {
            $resultat['departements'] = $this->getDepartements();
            $resultat['formations'] = $this->getFormations();

            $butFormationIds = [];

            foreach ($resultat['formations'] as $formation) {
                if (!$this->isButFormation($formation)) {
                    continue;
                }

                $formationId = $formation['formation_id'] ?? $formation['id'] ?? null;

                if ($formationId === null) {
                    continue;
                }

                $formationId = (int)$formationId;
                $butFormationIds[] = $formationId;
                $resultat['formations_but'][$formationId] = $formation;

                $resultat['referentiels_competences'][$formationId] =
                    $this->getReferentielCompetences($formationId);
            }

            foreach ($annees as $annee) {
                $formsemestres = $this->getFormsemestresByAnnee((int)$annee);
                $resultat['formsemestres'][$annee] = $formsemestres;

                foreach ($formsemestres as $fs) {
                    $formationId = $fs['formation_id'] ?? ($fs['formation']['formation_id'] ?? null);

                    if ($formationId === null || !in_array((int)$formationId, $butFormationIds, true)) {
                        continue;
                    }

                    $formsemestreId = $fs['id'] ?? $fs['formsemestre_id'] ?? null;

                    if ($formsemestreId === null) {
                        continue;
                    }

                    $formsemestreId = (int)$formsemestreId;
                    $resultat['formsemestres_but'][$annee][$formsemestreId] = $fs;
                    $resultat['decisions_jury'][$annee][$formsemestreId] =
                        $this->getDecisionsJury($formsemestreId);
                }
            }

        } catch (Throwable $e) {
            $resultat['erreurs'][] = $e->getMessage();
        }

        return $resultat;
    }
}