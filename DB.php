<?php 
use PDO;
use PDOException;
class DB {

    private static $pdo=null;


private static function getConnectionDB() {

    if (is_null(self::$pdo)) {

      require __DIR__ . '/config.php';
      try { 
        self::$pdo =new PDO(
            
            "mysql:host=" . DB_HOST. ";dbname=" . DB_NAME, DB_USER, DB_PASS,
            [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]

            );
        } catch (PDOException $e) {
            die("Erreur de connexion ") ;}
        }

        




        return self::$pdo;
    }
    public static function get() {
        return self::getConnectionDB();}
}   

?>