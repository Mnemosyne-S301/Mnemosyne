<?php

require_once __DIR__ . '/../config/config.php';

/**
 * Le DAO permettant d'acceder à la base de donnée Scolarite.
 * @package DAO
 */
class ScolariteDAO
{
    private $conn; // contains the PDO instance
    private static $instance = null;

    private function __construct()
    {
        // charset=utf8mb4 dans le DSN suffit, pas besoin de MYSQL_ATTR_INIT_COMMAND
        $this->conn = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', 
            DB_USER, 
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function getModel()
    {
        if (self::$instance === null)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Permet d'ajouter des etudiants à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatifs d'étudiant.
     * 
     * @param mixed[] $etudiants    Array structure contenant des array d'étudiants. 
     *                              Les array d'étudiant doivent contenir les clés 'code_nip' et 'etat'.
     * 
    */
    public function addEtudiant(array $etudiants)
    {
        // query writting 
        $query = "INSERT INTO Etudiant(code_nip, etat) VALUES ";
        for ($i = 0; $i < count($etudiants); $i++)
        {
            $query = $query . "(?, ?)"; // on utilie ? plutot que placeholders nommés avec : car plus simple dans la boucle
            if ($i < count($etudiants) - 1) //  pour recupérer les valeurs après
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // va recupérer toutes les valeurs du tableaux contenant les tableaux associatifs des étudiants
        $allEtudiantsValues = [];
        foreach ($etudiants as $etudiant)
        {
            $allEtudiantsValues[] = $etudiant['code_nip'];
            $allEtudiantsValues[] = $etudiant['etat'];
        }

        // execution de la requete 
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allEtudiantsValues);
        
    }

    /** Permet d'ajouter des departements à la base de données. Les données doivent être fournis dans
     * un array contenant array associatif représentant les departements. 
     * 
     * @param mixed[] $departements     Array structure contenant des array de departement. 
     *                                  Un array de departement contient les clés : 'dep_id' , 'accronyme', 'description', 'visible', 'date_creation', 'nom_dep' .
     * 
     */
    public function addDepartement(array $departements)
    {
        // query writting 
        $query = "INSERT INTO Departement(dep_id, accronyme, description, visible, date_creation, nom_dep) VALUES ";
        for ($i = 0; $i < count($departements); $i++)
        {
            $query = $query . "(?, ?, ?, ?, ?, ?)";
            if ($i < count($departements) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les departements
        $allDepartementsValues = [];
        foreach($departements as $dept)
        {
            $allDepartementsValues[] = $dept['dep_id'];
            $allDepartementsValues[] = $dept['accronyme'];
            $allDepartementsValues[] = $dept['description'];
            $allDepartementsValues[] = (int)$dept['visible'];   // cast bool to int for SQL issue
            $allDepartementsValues[] = $dept['date_creation'];
            $allDepartementsValues[] = $dept['nom_dep'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allDepartementsValues);
    }

    /** Permet d'ajouter des formations à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatif représentant les formations.
     * 
     * @param mixed[] $formations      Array structure contenant des array de formation.
     *                                 Un array de formation contient les clés : 'formation_id', 'accronyme', 'titre',
     *                                 'version', 'formation_code', 'type_parcours', 'titre_officiel', 'commentaire', 'code_specialite' .
     * 
     */
    public function addFormation(array $formations)
    {
        // query writting 
        $query = "INSERT INTO Formation(formation_id, accronyme, titre, version, formation_code, type_parcours, titre_officiel, commentaire, code_specialite, dep_id) VALUES ";
        for ($i = 0; $i < count($formations); $i++)
        {
            $query = $query . "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($i < count($formations) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les formations
        $allFormationsValues = [];
        foreach($formations as $form)
        {
            $allFormationsValues[] = $form['formation_id'];
            $allFormationsValues[] = $form['accronyme'];
            $allFormationsValues[] = $form['titre'];
            $allFormationsValues[] = $form['version'];
            $allFormationsValues[] = $form['formation_code'];
            $allFormationsValues[] = $form['type_parcours'];
            $allFormationsValues[] = $form['titre_officiel'];
            $allFormationsValues[] = $form['commentaire'];
            $allFormationsValues[] = $form['code_specialite'];
            $allFormationsValues[] = $form['dep_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allFormationsValues);
    }

    /** Permet d'ajouter des parcours à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatif représentant les parcours.
     * 
     * @param mixed[] $parcours       Array structure contenant des array de parcours.
     *                                Un array de parcours contient les clés : 'parcours_id', 'code', 'libelle', 'formation_id' .
     */
    public function addParcours(array $parcours)
    {
        // query writting
        $query = "INSERT INTO Parcours(parcours_id, code, libelle, formation_id) VALUES ";
        for ($i = 0; $i < count($parcours); $i++)
        {
            $query = $query . "(?, ?, ?, ?)";;
            if ($i < count($parcours) - 1)
            {
                $query = $query . ", "; 
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les parcours
        $allParcoursValues = [];
        foreach($parcours as $p)
        {
            $allParcoursValues[] = $p['parcours_id'];
            $allParcoursValues[] = $p['code'];
            $allParcoursValues[] = $p['libelle'];
            $allParcoursValues[] = $p['formation_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allParcoursValues);
    }

    /** Permet d'ajouter des années de formation à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatif représentant les différents années de formation.
     * 
     * @param mixed[] $anneesFormation  Array structure contenant des array de parcours.
     *                                  Un array de parcours contient les clés : 'ordre', 'parcours_id'.
     */
    public function addAnneeFormation(array $anneesFormation)
    {
        // query writting
        $query = "INSERT INTO AnneeFormation(ordre, parcours_id) VALUES ";
        for ($i = 0; $i < count($anneesFormation); $i++)
        {
            $query = $query . "(?, ?)";
            if ($i < count($anneesFormation) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les années de formation
        $allAnneeFormationValues = [];
        foreach($anneesFormation as $af)
        {
            $allAnneeFormationValues[] = $af['ordre'];
            $allAnneeFormationValues[] = $af['parcours_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allAnneeFormationValues);
    }

    public function addRCUE(array $rcues)
    {
        // query writting
        $query = "INSERT INTO RCUE(nomCompetence, niveau, anneeformation_id) ";
        for ($i = 0; $i < count($rcues); $i++)
        {
            $query = $query . " SELECT ? AS nomCompetence, ? AS niveau, anneeformation_id
                                FROM AnneeFormation AS AF
                                INNER JOIN Parcours AS P USING(parcours_id)
                                INNER JOIN Formation AS F USING(formation_id)
                                WHERE AF.ordre = ?
                                AND P.code = ?
                                AND F.formation_id = ?
                            ";
            if ($i < count($rcues) - 1)
            {
                $query = $query . " UNION ALL ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les RCUE
        $allRCUEValues = [];
        foreach($rcues as $rcue)
        {
            $allRCUEValues[] = $rcue['nomCompetence'];
            $allRCUEValues[] = $rcue['niveau'];
            $allRCUEValues[] = $rcue['ordre_anneeFormation'];
            $allRCUEValues[] = $rcue['code_parcours'];
            $allRCUEValues[] = $rcue['formation_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allRCUEValues);
    }

    public function addFormSemestre(array $formSemestres)
    {
        // query writting
        $query = "INSERT INTO FormSemestre(formsemestre_id, titre, semestre_num, date_debut, date_fin, titre_long, etape_apo, anneeformation_id) ";
        for ($i = 0; $i < count($formSemestres); $i++)
        {
            $query = $query . " SELECT ? AS formsemestre_id, ? AS titre, ? AS semestre_num, ? AS date_debut, ? AS date_fin, ? AS titre_long, ? AS etape_apo, anneeformation_id
                                FROM AnneeFormation AS AF 
                                INNER JOIN Parcours AS P USING(parcours_id)
                                WHERE AF.ordre = ?
                                AND P.code = ?
                                AND P.formation_id = ?
                            ";
            if ($i < count($formSemestres) - 1)
            {
                $query = $query . " UNION ALL ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les FormSemestre
        $allFormSemestreValues = [];
        foreach($formSemestres as $fs)
        {
            $allFormSemestreValues[] = $fs['formsemestre_id'];
            $allFormSemestreValues[] = $fs['titre'];
            $allFormSemestreValues[] = $fs['semestre_num'];
            $allFormSemestreValues[] = $fs['date_debut'];
            $allFormSemestreValues[] = $fs['date_fin'];
            $allFormSemestreValues[] = $fs['titre_long'];
            $allFormSemestreValues[] = $fs['etape_apo'];
            $allFormSemestreValues[] = $fs['ordre_anneeFormation'];
            $allFormSemestreValues[] = $fs['code_parcours'];
            $allFormSemestreValues[] = $fs['formation_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allFormSemestreValues);
    }

    public function addUE(array $ues)
    {
        // query writting
        $query = "INSERT INTO UE(ue_id, rcue_id, formsemestre_id) ";
        for ($i = 0; $i < count($ues); $i++)
        {
            $query = $query . " SELECT ? AS ue_id, rcue_id AS rcue_id, ? AS formsemetre_id
                                FROM RCUE
                                WHERE RCUE.nomCompetence = ?
                            ";
            if ($i < count($ues) - 1)
            {
                $query = $query . " UNION ALL ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les UE
        $allUEValues = [];
        foreach($ues as $ue)
        {
            $allUEValues[] = $ue['ue_id'];
            $allUEValues[] = $ue['formsemestre_id'];
            $allUEValues[] = $ue['nomCompetence'];
            
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allUEValues);
    }

    public function addCodeAnnee(array $codeAnnees)
    {
        // query writting
        $query = "INSERT INTO CodeAnnee(code, signification) VALUES ";
        for ($i = 0; $i < count($codeAnnees); $i++)
        {
            $query = $query . "(?, ?)";
            if ($i < count($codeAnnees) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les CodeAnnee
        $allCodeAnneeValues = [];
        foreach($codeAnnees as $ca)
        {
            $allCodeAnneeValues[] = $ca['code'];
            $allCodeAnneeValues[] = $ca['signification'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allCodeAnneeValues);
    }

    public function addCodeRCUE(array $codeRCUEs)
    {
        // query writting
        $query = "INSERT INTO CodeRCUE(code, signification) VALUES ";
        for ($i = 0; $i < count($codeRCUEs); $i++)
        {
            $query = $query . "(?, ?)";
            if ($i < count($codeRCUEs) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les CodeAnnee
        $allCodeRCUEsValues = [];
        foreach($codeRCUEs as $c)
        {
            $allCodeRCUEsValues[] = $c['code'];
            $allCodeRCUEsValues[] = $c['signification'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allCodeRCUEsValues);
    }

    public function addCodeUE(array $codeUEs)
    {
        // query writting
        $query = "INSERT INTO CodeUE(code, signification) VALUES ";
        for ($i = 0; $i < count($codeUEs); $i++)
        {
            $query = $query . "(?, ?)";
            if ($i < count($codeUEs) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les CodeAnnee
        $allCodeUEsValues = [];
        foreach($codeUEs as $c)
        {
            $allCodeUEsValues[] = $c['code'];
            $allCodeUEsValues[] = $c['signification'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allCodeUEsValues);
    }

    /** Permet d'ajouter des EffectuerAnnee à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatifs.
     * 
     * ATTENTION peut poser problème si on enregistre deux fois, pour le même étudiant, la même année scolaire,
     * des formsemestre qui sont de la même année de formation. 
     * Il est donc conseillé de ne renseigner que le dernier semestre de chaque année. (Les semestres pairs.)
     * 
     * @param mixed[] $effectuerAnnees  Array structure contenant des array de parcours.
     *                                  Un array de de effectuerAnnees contient les clés suivante :
     *                                      'annee_scolaire' : l'année scolaire (ex : 2021) 
     *                                      'code_nip' : le hash du code nip de l'étudiant (doit être valeur de la table Etudiant)
     *                                      'formsemestre_id' : l'id d'un formesemestre de l'année de formation. (doit être renseigné dans la table Formsemestre)
     *                                      'code' : le code obtenu pour l'année (ex : ADM, AJ, etc.) (doit être valeur de la table CodeAnnee)
     */
    public function addEffectuerAnnee(array $effectuerAnnees)
    {
        // query writting
        $query = "INSERT INTO EffectuerAnnee(annee_scolaire, anneeformation_id, etudiant_id, codeannee_id)";
        for ($i = 0; $i < count($effectuerAnnees); $i++)
        {
            $query = $query . " SELECT ? AS annee_scolaire, B.anneeformation_id, A.etudiant_id, C.codeannee_id
                                FROM (
                                    SELECT etudiant_id
                                    FROM Etudiant
                                    WHERE code_nip = ?
                                ) AS A,
                                (
                                    SELECT anneeformation_id
                                    FROM AnneeFormation
                                    INNER JOIN FormSemestre USING(anneeformation_id)
                                    WHERE formsemestre_id = ?
                                ) AS B,
                                (
                                    SELECT codeannee_id
                                    FROM CodeAnnee
                                    WHERE code = ?
                                ) AS C
                            ";
            if ($i < count($effectuerAnnees) - 1)
            {
                $query = $query . " UNION ALL ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les EffectuerAnnee
        $allEffectuerAnneeValues = [];
        foreach($effectuerAnnees as $ea)
        {
            $allEffectuerAnneeValues[] = $ea['annee_scolaire'];
            $allEffectuerAnneeValues[] = $ea['code_nip'];
            $allEffectuerAnneeValues[] = $ea['formsemestre_id'];
            $allEffectuerAnneeValues[] = $ea['code'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allEffectuerAnneeValues);
    }

    /** Permet d'ajouter des EffectuerRCUE à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatifs.
     * 
     * @param mixed[] $effectuerRCUEs   Array structure contenant des array de parcours.
     *                                  Un array de de effectuerRCUEs contient les clés suivante :
     *                                      'annee_scolaire' : l'année scolaire (ex : 2021) 
     *                                      'code_nip' : le hash du code nip de l'étudiant (doit être valeur de la table Etudiant)
     *                                      'ue_id' : l'id d'un des UE composant cette RCUE. Sans ça, la RCUE ne peut être identifiée.
     *                                      'code_rcue' : le code de la decision obtenu sur cette RCUE (ex : ADM, AJ, etc.) (doit être valeur de la table CodeRCUE)
     */
    public function addEffectuerRCUE(array $effectuerRCUEs)
    {
        // query writting
        $query = "INSERT INTO EffectuerRCUE(annee_scolaire, rcue_id, etudiant_id, codercue_id)";
        for ($i = 0; $i < count($effectuerRCUEs); $i++)
        {
            $query = $query . " SELECT ? AS annee_scolaire, B.rcue_id, A.etudiant_id, C.codercue_id
                                FROM (
                                    SELECT etudiant_id
                                    FROM Etudiant
                                    WHERE code_nip = ?
                                ) AS A,
                                (
                                    SELECT rcue_id
                                    FROM RCUE
                                    INNER JOIN UE USING(rcue_id)
                                    WHERE ue_id = ?
                                ) AS B,
                                (
                                    SELECT codercue_id
                                    FROM CodeRCUE
                                    WHERE code = ?
                                ) AS C";
            if ($i < count($effectuerRCUEs) - 1)
            {
                $query = $query . " UNION ALL ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les EffectuerRCUE
        $allEffectuerRCUEValues = [];
        foreach($effectuerRCUEs as $ercue)
        {
            $allEffectuerRCUEValues[] = $ercue['annee_scolaire'];
            $allEffectuerRCUEValues[] = $ercue['code_nip'];
            $allEffectuerRCUEValues[] = $ercue['ue_id'];
            $allEffectuerRCUEValues[] = $ercue['code_rcue'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allEffectuerRCUEValues);
    }

    /** Permet d'ajouter des EffectuerUE à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatifs.
     * 
     * @param mixed[] $effectuerUEs     Array structure contenant des array de parcours.
     *                                  Un array de de effectuerUEs contient les clés suivante :
     *                                      'annee_scolaire' : l'année scolaire (ex : 2021) 
     *                                      'code_nip' : le hash du code nip de l'étudiant (doit être valeur de la table Etudiant)
     *                                      'ue_id' : l'id de l'UE. Permet de l'identifier. 
     *                                      'code_rcue' : le code de la decision obtenu sur cette UE (ex : ADM, AJ, etc.) (doit être valeur de la table CodeUE)
     */
    public function addEffectuerUE(array $effectuerUEs)
    {
        // query writting
        $query = "INSERT INTO EffectuerUE(annee_scolaire, ue_id, etudiant_id, codeue_id)";
        for ($i = 0; $i < count($effectuerUEs); $i++)
        {
            $query = $query . " SELECT ? AS annee_scolaire, ? AS ue_id, A.etudiant_id, C.codeue_id
                                FROM (
                                    SELECT etudiant_id
                                    FROM Etudiant
                                    WHERE code_nip = ?
                                ) AS A,
                                (
                                    SELECT codeue_id
                                    FROM CodeUE
                                    WHERE code = ?
                                ) AS C";
            if ($i < count($effectuerUEs) - 1)
            {
                $query = $query . " UNION ALL ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les EffectuerRCUE
        $allEffectuerUEValues = [];
        foreach($effectuerUEs as $eue)
        {
            // ATTENTION l'ordre des valeurs est différente car l'ordre des arguments dans la requête est différente
            $allEffectuerUEValues[] = $eue['annee_scolaire'];
            $allEffectuerUEValues[] = $eue['ue_id'];
            $allEffectuerUEValues[] = $eue['code_nip'];
            $allEffectuerUEValues[] = $eue['code_ue'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allEffectuerUEValues);
    }

     public function resetDatabase(){
        $this->conn->exec("SET FOREIGN_KEY_CHECKS=0;");
        $tables =['EffectuerUE',
        'EffectuerRCUE',
        'EffectuerAnnee',
        'UE',
        'FormSemestre',
        'RCUE',
        'AnneeFormation',
        'Parcours',
        'Formation',
        'CodeUE',
        'CodeRCUE',
        'CodeAnnee',
        'Etudiant',
        'Departement'
    ];
    foreach($tables as $table){
        $this->conn->exec("TRUNCATE FROM $table;");
    }
    $this->conn->exec("SET FOREIGN_KEY_CHECKS=1;");
    }

    // =========================================================================
    // METHODES DE LECTURE (pour le diagramme Sankey)
    // =========================================================================

    /**
     * Mapping des acronymes simplifiés vers les patterns de recherche en base.
     * Permet de faire correspondre les codes courts (INFO, GEA) aux acronymes stockés.
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

    /**
     * Récupère la cohorte d'étudiants pour une année scolaire et une formation.
     * Utilisé pour alimenter le diagramme Sankey.
     * 
     * IMPORTANT: Cette méthode suit une VRAIE cohorte, c'est-à-dire les étudiants
     * qui sont entrés en BUT1 à l'année de départ de la cohorte.
     *
     * @param int $anneeScolaire L'année scolaire à récupérer (ex: 2021, 2022, 2023)
     * @param string $formationAccronyme L'acronyme de la formation (ex: INFO, GEA, CJ)
     * @param int|null $anneeCohorte L'année d'entrée de la cohorte (si null, = anneeScolaire)
     * @return array Liste des étudiants avec leurs décisions annuelles
     *               Format: [['etudid', 'etat', 'ordre', 'code', 'annee_scolaire'], ...]
     */
    public function getCohorteParAnneeEtFormation(int $anneeScolaire, string $formationAccronyme, ?int $anneeCohorte = null): array
    {
        $pattern = $this->getFormationPattern($formationAccronyme);
        
        // Si pas d'année de cohorte spécifiée, on prend l'année demandée
        if ($anneeCohorte === null) {
            $anneeCohorte = $anneeScolaire;
        }
        
        // Requête qui suit une vraie cohorte:
        // 1. Sous-requête pour identifier les étudiants entrés en BUT1 à l'année de cohorte
        // 2. Récupère les données de ces étudiants pour l'année demandée
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
              AND e.etudiant_id IN (
                  -- Sous-requête: étudiants entrés en BUT1 à l'année de cohorte
                  SELECT DISTINCT ea2.etudiant_id
                  FROM EffectuerAnnee ea2
                  INNER JOIN AnneeFormation af2 
                      ON af2.anneeformation_id = ea2.anneeformation_id
                  INNER JOIN Parcours p2 
                      ON p2.parcours_id = af2.parcours_id
                  INNER JOIN Formation f2 
                      ON f2.formation_id = p2.formation_id
                  WHERE ea2.annee_scolaire = :annee_cohorte
                    AND af2.ordre = 1
                    AND f2.accronyme LIKE :formation2
              )
        ";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':annee', $anneeScolaire, PDO::PARAM_INT);
            $stmt->bindValue(':annee_cohorte', $anneeCohorte, PDO::PARAM_INT);
            $stmt->bindValue(':formation', $pattern, PDO::PARAM_STR);
            $stmt->bindValue(':formation2', $pattern, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[ERREUR SQL] Requete getCohorteParAnneeEtFormation : ' . $e->getMessage());
            throw new Exception('Erreur SQL getCohorteParAnneeEtFormation : ' . $e->getMessage());
        }
    }

}
?>