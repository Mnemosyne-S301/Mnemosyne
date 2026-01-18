<?php

require_once __DIR__ . '/../config/config.php';

/**
 * Classe d'accès aux données (DAO) pour les statistiques.
 * Gère la connexion à la base de données et la récupération des informations
 * sur les effectifs et la répartition des résultats (UE).
 * Implémente le pattern Singleton.
 */
class StatsDAO {
    
    /**
     * @var PDO $conn Instance de connexion à la base de données.
     */
    private $conn;

    /**
     * @var StatsDAO|null $instance Instance unique de la classe.
     */
    private static $instance = null; 

    /**
     * Constructeur privé.
     * Établit la connexion à la base de données.
     */
    private function __construct()
    {
        $this->conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . STATS_DB_NAME, DB_USER, STATS_DB_PASS);
    }

    /**
     * Récupère l'instance unique du DAO (Singleton).
     *
     * @return StatsDAO L'instance de la classe.
     */
    public static function getModel()
    {
        if (self::$instance === null)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Récupère le nombre d'élèves pour une formation donnée.
     *
     * @param string $formation Le nom de la formation.
     * @return array Tableau des résultats.
     */
    public function getNbEleveParFormation($formation){

        $query = "SELECT annee_scolaire, departement, parcours, nombre_etudiants
                FROM nb_eleve_par_formation
                WHERE formation = :formation ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':formation', $formation, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le nombre d'élèves pour une année scolaire donnée.
     *
     * @param int|string $annee L'année scolaire.
     * @return array Tableau des résultats.
     */
    public function getNbEleveParAnnee($annee){

        $query = "SELECT departement, formation, parcours, nombre_etudiants
                    FROM nb_eleve_par_formation
                    WHERE annee_scolaire = :annee";
        
        $stmt= $this->conn->prepare($query);
        $stmt->bindValue(':annee', $annee, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le nombre d'élèves pour un parcours donné.
     *
     * @param string $parcours Le nom du parcours.
     * @return array Tableau des résultats.
     */
    public function getNbEleveParParcours($parcours){

        $query = "SELECT annee_scolaire, departement, formation, nombre_etudiants
                    FROM nb_eleve_par_formation
                    WHERE parcours = :parcours";
        
        $stmt= $this->conn->prepare($query);
        $stmt->bindValue(':parcours', $parcours, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la répartition des UE validées (ADMIS) pour un parcours donné.
     *
     * @param string $parcours Le nom du parcours.
     * @return array Tableau des résultats.
     */
    public function getNbRepartitionUEADMISParParcours($parcours){

        // Correction : foramtion -> formation
        $query = "SELECT dep, formation, annee_scolaire, nb_ue_validees, nb_eleves
                    FROM repartition_notes_par_parcours
                    WHERE parcours = :parcours";

        $stmt =$this->conn->prepare($query);
        $stmt->bindValue(':parcours', $parcours, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la répartition des UE validées (ADMIS) pour une formation donnée.
     *
     * @param string $formation Le nom de la formation.
     * @return array Tableau des résultats.
     */
    public function getNbRepartitionUEADMISParFormation($formation){

        // Correction : parours -> parcours, foramtion -> formation
        $query = "SELECT dep, parcours, annee_scolaire, nb_ue_validees, nb_eleves
                    FROM repartition_notes_par_parcours
                    WHERE formation = :formation";

        $stmt =$this->conn->prepare($query);
        $stmt->bindValue(':formation', $formation, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la répartition des UE validées (ADMIS) pour une année scolaire donnée.
     *
     * @param int|string $annee L'année scolaire.
     * @return array Tableau des résultats.
     */
    public function getNbRepartitionUEADMISParAnnee($annee){

        // Correction : parours -> parcours, annee -> annee_scolaire
        $query = "SELECT dep, parcours, formation, nb_ue_validees, nb_eleves
                    FROM repartition_notes_par_parcours
                    WHERE annee_scolaire = :annee";

        $stmt =$this->conn->prepare($query);
        $stmt->bindValue(':annee', $annee, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Calcule le total précis d'étudiants pour une formation et une année.
     * C'est beaucoup plus performant que de récupérer toutes les lignes et de compter en PHP.
     * * @param string $formation
     * @param int|string $annee
     * @return int Le nombre total d'étudiants.
     */
    public function getEffectifTotalPrecise($formation, $annee) {
        $query = "SELECT SUM(nombre_etudiants) 
                  FROM nb_eleve_par_formation 
                  WHERE formation = :formation AND annee_scolaire = :annee";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':formation', $formation, PDO::PARAM_STR);
        $stmt->bindValue(':annee', $annee, PDO::PARAM_STR);
        $stmt->execute();

        // Retourne directement le chiffre (int)
        return (int) $stmt->fetchColumn();
    }

    /**
     * Mapping des acronymes simplifiés vers les patterns de recherche en base
     */
    private function getFormationPattern(string $acronyme): string
    {
        $mapping = [
            'INFO' => '%INFO%',
            'GEA' => '%GEA%',
            'GEII' => '%GEII%',
            'RT' => '%R&T%',
            'CJ' => '%CJ%',
            'SD' => '%SD%',
            'STID' => '%STID%',
        ];
        
        return $mapping[strtoupper($acronyme)] ?? '%' . $acronyme . '%';
    }

    public function getCohorteParAnneeEtFormation(int $anneeScolaire, string $formationAccronyme): array
    {
        // Utiliser une connexion à la base scolarite (pas stats)
        $connScolarite = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
        
        $pattern = $this->getFormationPattern($formationAccronyme);
        
        $sql = "
            SELECT
                e.etudiant_id AS etudid,
                e.etat AS etat,
                af.ordre AS ordre,
                ca.code AS code,
                ea.annee_scolaire AS annee_scolaire
            FROM EffectuerAnnee ea
            INNER JOIN Etudiant e 
                ON e.etudiant_id = ea.etudiant_id
            INNER JOIN AnneeFormation af 
                ON af.anneeformation_id = ea.anneeformation_id
            INNER JOIN Parcours p 
                ON p.parcours_id = af.parcours_id
            INNER JOIN Formation f 
                ON f.formation_id = p.formation_id
            INNER JOIN CodeAnnee ca 
                ON ca.codeannee_id = ea.codeannee_id
            WHERE ea.annee_scolaire = :annee
              AND f.accronyme LIKE :formation
        ";

        $stmt = $connScolarite->prepare($sql);
        $stmt->bindValue(':annee', $anneeScolaire, PDO::PARAM_INT);
        $stmt->bindValue(':formation', $pattern, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
