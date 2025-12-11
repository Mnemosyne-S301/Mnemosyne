<?php

class StatsDAO
{
    private $conn; // contains the PDO instance
    private static $instance = null;

    private function __construct()
    {
        $this->conn = new PDO('mysql:host=localhost;dbname=Mnemosyne', 'phpserv', 'mdptest');
        // l'utilisateur ici est phpserv avec comme mot de passe mdptest . Pensez enventuellement à changer ça selon votre configuration.
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
            $allDepartementsValues[] = $dept['visible'];
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
        $query = "INSERT INTO RCUE(nomCompetence, niveau, anneeformation_id) VALUES ";
        for ($i = 0; $i < count($rcues); $i++)
        {
            $query = $query . "(?, ?, ?)";
            if ($i < count($rcues) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les RCUE
        $allRCUEValues = [];
        foreach($rcues as $rcue)
        {
            $allRCUEValues[] = $rcue['nomCompetence'];
            $allRCUEValues[] = $rcue['niveau'];
            $allRCUEValues[] = $rcue['anneeformation_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allRCUEValues);
    }

    public function addFormSemestre(array $formSemestres)
    {
        // query writting
        $query = "INSERT INTO FormSemestre(formsemestre_id, titre, semestre_num, date_debut, date_fin, titre_long, etape_apo, anneeformation_id) VALUES ";
        for ($i = 0; $i < count($formSemestres); $i++)
        {
            $query = $query . "(?, ?, ?, ?, ?, ?, ?, ?)";
            if ($i < count($formSemestres) - 1)
            {
                $query = $query . ", ";
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
            $allFormSemestreValues[] = $fs['anneeformation_id'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allFormSemestreValues);
    }

    public function addUE(array $ues)
    {
        // query writting
        $query = "INSERT INTO UE(ue_id, rcue_id, formsemestre_id) VALUES ";
        for ($i = 0; $i < count($ues); $i++)
        {
            $query = $query . "(?, ?, ?)";
            if ($i < count($ues) - 1)
            {
                $query = $query . ", ";
            }
        }
        $query = $query . ";";

        // récupération des valeurs du tableau de tableaux représentants les UE
        $allUEValues = [];
        foreach($ues as $ue)
        {
            $allUEValues[] = $ue['ue_id'];
            $allUEValues[] = $ue['rcue_id'];
            $allUEValues[] = $ue['formsemestre_id'];
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

    /** Permet d'ajouter des EffectuerAnnee à la base de données. Les données doivent être fournis dans
     * un array contenant des array associatifs.
     * 
     * @param mixed[] $effectuerAnnees  Array structure contenant des array de parcours.
     *                                  Un array de de effectuerAnnees contient les clés suivante :
     *                                      'annee_scolaire' : l'année scolaire (ex : 2021) 
     *                                      'code_nip' : le hash du code nip de l'étudiant (doit être valeur de la table Etudiant)
     *                                      'parcours_code' : le code du parcours (doit être valeur de la table Parcours)
     *                                      'code_annee' : le code obtenu pour l'année (ex : ADM, AJ, etc.) (doit être valeur de la table CodeAnnee)
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
                                    INNER JOIN Parcours USING(parcours_id)
                                    WHERE formsemestre_id = 1
                                    AND code = ?
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
            $allEffectuerAnneeValues[] = $ea['parcours_code'];
            $allEffectuerAnneeValues[] = $ea['code_annee'];
        }

        // execution de la requete
        $stmt = $this->conn->prepare($query);
        $stmt->execute($allEffectuerAnneeValues);
    }

    public function addEffectuerRCUE(array $effectuerRCUEs)
    {
        /*
        À FAIRE ...
        */
    }

    public function addEffectuerUE(array $effectuerUEs)
    {
        /*
        À FAIRE..
        */
    }

}
?>