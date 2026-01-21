<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

echo "=== Formations BUT avec étudiants en 2021-2024 ===\n";
$sql = "SELECT f.accronyme, af.ordre, ea.annee_scolaire, COUNT(DISTINCT ea.etudiant_id) as nb
        FROM EffectuerAnnee ea
        INNER JOIN AnneeFormation af ON af.anneeformation_id = ea.anneeformation_id
        INNER JOIN Parcours p ON p.parcours_id = af.parcours_id
        INNER JOIN Formation f ON f.formation_id = p.formation_id
        WHERE f.accronyme LIKE 'BUT%'
        GROUP BY f.accronyme, af.ordre, ea.annee_scolaire
        ORDER BY f.accronyme, ea.annee_scolaire, af.ordre";
$stmt = $pdo->query($sql);
$current = '';
while($row = $stmt->fetch()) { 
    if ($current != $row['accronyme']) {
        $current = $row['accronyme'];
        echo "\n" . $row['accronyme'] . ":\n";
    }
    echo "  {$row['annee_scolaire']} BUT{$row['ordre']}: {$row['nb']} étudiants\n";
}
