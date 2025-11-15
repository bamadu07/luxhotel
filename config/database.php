<?php
// config/database.php
$host = 'localhost';
$dbname = 'hotel_db';
$username = 'root'; // À modifier selon votre configuration
$password = ''; // À modifier selon votre configuration

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion: " . $e->getMessage());
}
?>