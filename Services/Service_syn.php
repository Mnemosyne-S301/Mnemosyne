<?php
require_once __DIR__ . "/../Models/ScolariteDAO.php";
//require_once __DIR__ . "../Models/SourceDataDAO.php";
require_once __DIR__ . "/../Models/JsonDAO.php";
//require_once __DIR__ . "../Models/ScodocDAO.php";

class Service_syn
{
    private static ?ScolariteDAO $scolariteDAO = null;
    private static $sourcedataDAO = null;

    public function __construct()
    {
        if (self::$scolariteDAO === null)
        {
            self::$scolariteDAO = ScolariteDAO::getModel();
        }

        if (self::$sourcedataDAO === null)
        {
            self::$sourcedataDAO = new JsonDAO();
        }
    }

    public function sync_etudiant()
    {
        $all_etudiant = self::$sourcedataDAO->findall_etudiant();

        $all_etudiant_dict = [];
        foreach($all_etudiant as $e)
        {
            $all_etudiant_dict[] = $e->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addEtudiant($all_etudiant_dict);
    }

    public function sync_departement()
    {
        $all_departement = self::$sourcedataDAO->findall_departement();

        $all_departement_dict = [];
        foreach($all_departement as $d)
        {
            $all_departement_dict[] = $d->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addDepartement($all_departement_dict);
    }

    public function sync_formation()
    {
        $all_formation = self::$sourcedataDAO->findall_formation();

        $all_formation_dict = [];
        foreach($all_formation as $f)
        {
            $all_formation_dict[] = $f->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addFormation($all_formation_dict);
    }

    public function sync_parcours()
    {
        $all_parcours = self::$sourcedataDAO->findall_parcours();

        $all_parcours_dict = [];
        foreach($all_parcours as $p)
        {
            $all_parcours_dict[] = $p->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addParcours($all_parcours_dict);
    }

    public function sync_anneeFormation()
    {
        $all_anneeFormation = self::$sourcedataDAO->findall_anneeFormation();

        $all_anneeFormation_dict = [];
        foreach($all_anneeFormation as $af)
        {
            $all_anneeFormation_dict[] = $af->toDict();
        }

        // remplissage de la base de donnée
        self::$scolariteDAO->addAnneeFormation($all_anneeFormation_dict);
    }
}
?>