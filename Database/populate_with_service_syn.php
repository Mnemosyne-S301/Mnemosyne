<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../Services/Service_syn.php';
require_once __DIR__ . '/../Models/ScolariteDAO.php';

echo "Réinitialisation de la base de données...\n";
$dao = ScolariteDAO::getModel();
$dao->resetDatabase();
echo "Tables vidées.\n";

echo "Début du peuplement...\n";
$s = new Service_syn();
$s->sync_all();
echo "Peuplement terminé.\n";