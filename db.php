<?php
// Configuration de la session pour qu'elle dure plus longtemps (1 semaine)
ini_set('session.gc_maxlifetime', 604800);
session_set_cookie_params(604800);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. On charge la configuration sécurisée
$config = require 'config.php';
define('TMDB_API_KEY', $config['tmdb_key']); // NOUVEAU



// 2. On utilise les variables du fichier de configuration
$host = $config['db_host'];
$db   = $config['db_name'];
$user = $config['db_user'];
$pass = $config['db_pass'];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion BDD : Le service est momentanément indisponible.");
}