<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
