<?php
require_once __DIR__ . '/../Models/DB.php';

// Afficher les tables présentes
try {
    $pdo = DB::get();
    echo "Tables in DB:\n";
    $res = $pdo->query("SHOW TABLES");
    foreach ($res->fetchAll(PDO::FETCH_NUM) as $row) {
        echo $row[0] . "\n";
    }

    echo "\nTrying DESCRIBE Users:\n";
    try {
        $r = $pdo->query("DESCRIBE Users");
        foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo $col['Field'] . "\t" . $col['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "DESCRIBE Users failed: " . $e->getMessage() . "\n";
    }

    echo "\nTrying DESCRIBE users:\n";
    try {
        $r = $pdo->query("DESCRIBE users");
        foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo $col['Field'] . "\t" . $col['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "DESCRIBE users failed: " . $e->getMessage() . "\n";
    }

    echo "\nListing via DAO (if possible):\n";
    require_once __DIR__ . '/../Models/UserDAO.php';
    $dao = new UserDAO();
    $users = $dao->findAll();
    if (empty($users)) {
        echo "No users returned by DAO\n";
        exit(0);
    }
    foreach ($users as $u) {
        echo $u->getId() . "\t" . $u->getUsername() . "\t" . $u->getRole() . "\n";
    }

} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}
