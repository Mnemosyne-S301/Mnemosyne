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
}