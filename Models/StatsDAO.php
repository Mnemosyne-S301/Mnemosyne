<?php

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
        $this->conn = new PDO('mysql:host=localhost;dbname=Stats', 'root', '1234');
        // l'utilisateur ici est phpserv avec comme mot de passe mdptest . Pensez enventuellement à changer ça selon votre configuration.
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

    public function getCohorteParAnneeEtFormation(int $anneeScolaire, string $formationAccronyme): array
{
    $sql = "
        SELECT
            e.etudiant_id AS etudid,
            e.etat AS etat,
            af.ordre AS ordre,
            ca.code AS code,
            ea.annee_scolaire AS annee_scolaire
        FROM scolarite.effectuerannee ea
        INNER JOIN scolarite.etudiant e 
            ON e.etudiant_id = ea.etudiant_id
        INNER JOIN scolarite.anneeformation af 
            ON af.anneeformation_id = ea.anneeformation_id
        INNER JOIN scolarite.parcours p 
            ON p.parcours_id = af.parcours_id
        INNER JOIN scolarite.formation f 
            ON f.formation_id = p.formation_id
        INNER JOIN scolarite.codeannee ca 
            ON ca.codeannee_id = ea.codeannee_id
        WHERE ea.annee_scolaire = :annee
          AND f.accronyme = :formation
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':annee', $anneeScolaire, PDO::PARAM_INT);
    $stmt->bindValue(':formation', $formationAccronyme, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
