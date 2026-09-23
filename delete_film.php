<?php
require "db.php";
require "auth.php";

// Vérification de sécurité + Vérification du jeton CSRF
if (
    isset($_GET['id']) && 
    isset($_SESSION['active_group_id']) && 
    isset($_GET['token']) && 
    hash_equals($_SESSION['csrf_token'], $_GET['token'])
) {
    $film_id = intval($_GET['id']);
    $group_id = $_SESSION['active_group_id'];

    $stmt = $pdo->prepare("DELETE FROM films WHERE id = ? AND group_id = ?");
    $stmt->execute([$film_id, $group_id]);
}

header("Location: dashboard.php");
exit;
?>