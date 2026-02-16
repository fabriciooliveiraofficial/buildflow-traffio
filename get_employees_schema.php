<?php
require_once 'index.php';
$db = new App\Core\Database();
$columns = $db->fetchAll('DESCRIBE employees');
header('Content-Type: application/json');
echo json_encode($columns, JSON_PRETTY_PRINT);
