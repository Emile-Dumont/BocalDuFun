<?php
require "db.php";
require "auth.php";

header('Content-Type: application/json');

if (!isset($_SESSION["active_group_id"])) {
    echo json_encode(null);
    exit;
}

// Récupération des filtres depuis l'URL
$genre = $_GET['genre'] ?? '';
$runtime = $_GET['runtime'] ?? '';
$provider = $_GET['provider'] ?? '';

// Construction dynamique de la requête SQL
$sql = "SELECT * FROM films WHERE group_id = ? AND seen = 0";
$params = [$_SESSION["active_group_id"]];

if (!empty($genre)) {
    $sql .= " AND genres LIKE ?";
    $params[] = "%" . $genre . "%";
}

if (!empty($runtime)) {
    $sql .= " AND runtime <= ?";
    $params[] = intval($runtime);
}

if (!empty($provider)) {
    $sql .= " AND providers LIKE ?";
    $params[] = "%" . $provider . "%";
}

$sql .= " ORDER BY RAND() LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$film = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($film ? $film : null);
?>