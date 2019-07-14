<?php
define('DB_HOST','localhost');
define('DB_DBNAME','rusixlistr_sams');
define('DB_USERNAME','rusixlistr_sams');
define('DB_PASSWORD','sSyUA89RRxgZ');
//~ define('DB_PREFIX','A_');

try {
    $hDB = $hDB ?? new PDO('mysql:host='.DB_HOST.';dbname='.DB_DBNAME, DB_USERNAME, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}





