<?php
$dsn = "pgsql:host=postgres;dbname=hcamp2025";

$db = new PDO($dsn, 'postgres', 'postgres', [
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

?>