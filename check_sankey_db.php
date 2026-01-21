<?php
// Script de diagnostic DB pour le Sankey
// Usage: php check_sankey_db.php

require_once __DIR__ . '/config/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    fwrite(STDERR, "Erreur connexion DB: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$queries = [
    'count_effectuer_annee' => 'SELECT COUNT(*) AS cnt FROM EffectuerAnnee;',
    'count_etudiant' => 'SELECT COUNT(*) AS cnt FROM Etudiant;',
    'count_codeannee' => 'SELECT COUNT(*) AS cnt FROM CodeAnnee;',
    'count_formation' => 'SELECT COUNT(*) AS cnt FROM Formation;',
    'distinct_accronyme' => 'SELECT DISTINCT accronyme FROM Formation LIMIT 100;',
    'codes_codeannee' => 'SELECT code FROM CodeAnnee LIMIT 100;',
    'effectuer_by_code_and_year' => "SELECT ea.annee_scolaire AS annee, ca.code AS code, COUNT(*) AS cnt\n  FROM EffectuerAnnee ea\n  JOIN CodeAnnee ca ON ea.codeannee_id = ca.codeannee_id\n  GROUP BY ca.code, ea.annee_scolaire\n  ORDER BY ea.annee_scolaire, ca.code LIMIT 200;",
    'sample_effectuer_rows' => 'SELECT ea.annee_scolaire, ea.anneeformation_id, ea.etudiant_id, ca.code FROM EffectuerAnnee ea JOIN CodeAnnee ca USING(codeannee_id) LIMIT 50;'
];

$results = [];
foreach ($queries as $key => $sql) {
    try {
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results[$key] = $rows;
    } catch (Throwable $e) {
        $results[$key] = ['error' => $e->getMessage()];
    }
}

// Afficher JSON et résumé lisible
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

// Affichage synthétique
echo "\nRésumé rapide:\n";
if (isset($results['count_effectuer_annee'][0]['cnt'])) {
    echo "EffectuerAnnee: " . $results['count_effectuer_annee'][0]['cnt'] . "\n";
}
if (isset($results['count_etudiant'][0]['cnt'])) {
    echo "Etudiant: " . $results['count_etudiant'][0]['cnt'] . "\n";
}
if (isset($results['count_codeannee'][0]['cnt'])) {
    echo "CodeAnnee: " . $results['count_codeannee'][0]['cnt'] . "\n";
}
if (isset($results['count_formation'][0]['cnt'])) {
    echo "Formation: " . $results['count_formation'][0]['cnt'] . "\n";
}

echo "\nFichier créé: check_sankey_db.php\n";

exit(0);
