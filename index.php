<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);


<<<<<<< HEAD
$controllers = ["index"]; //Liste des contrôleurs
$controller_default = "index"; //Nom du contrôleur par défaut
=======
$controllers = ["accueil","auth","admin"]; //Liste des contrôleurs
$controller_default = "accueil"; //Nom du contrôleur par défaut
>>>>>>> logAdmin

//On teste si le paramètre controller existe et correspond à un contrôleur
//de la liste $controllers
if(isset($_GET['controller']) and in_array($_GET['controller'], $controllers))
{
    $nom_controller = $_GET['controller'];
}
else
{
    $nom_controller = $controller_default;
}
//On détermine le nom de la classe du contrôleur
$nom_classe = 'Controller_' . $nom_controller;

//On détermine le nom du fichier contenant la définition du contrôleur
$nom_fichier = 'Controllers/' . $nom_classe . '.php';

//Si le fichier existe et est accessible en lecture
if (is_readable($nom_fichier))
{
//On l'inclut et on instancie un objet de cette classe
require_once $nom_fichier;
new $nom_classe();
}
else
{
    die("Error 404: not found!");
}
?>