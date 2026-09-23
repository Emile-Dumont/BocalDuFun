<?php
require "db.php";
require "auth.php";

if (
    isset($_GET['id']) && 
    isset($_SESSION['active_group_id']) && 
    isset($_GET['token']) && 
    hash_equals($_SESSION['csrf_token'], $_GET['token'])
) {
    $film_id = intval($_GET['id']);
    $group_id = $_SESSION['active_group_id'];

    $stmt = $pdo->prepare("UPDATE films SET seen = NOT seen WHERE id = ? AND group_id = ?");
    $stmt->execute([$film_id, $group_id]);
}

$redirect = "dashboard.php";
if (isset($_SERVER['HTTP_REFERER'])) {
    $redirect = $_SERVER['HTTP_REFERER'];
}

header("Location: " . $redirect);
exit;