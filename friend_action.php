<?php
require "db.php";
require "auth.php";

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $target_id = intval($_GET['id']);
    $my_id = $_SESSION['user_id'];

    if ($action === 'add') {
        // Vérifie que l'amitié n'existe pas déjà
        $stmt = $pdo->prepare("SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$my_id, $target_id, $target_id, $my_id]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$my_id, $target_id]);
        }
    } 
    elseif ($action === 'accept') {
        // Accepter la demande reçue
        $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE friend_id = ? AND user_id = ?");
        $stmt->execute([$my_id, $target_id]);
    } 
    elseif ($action === 'remove' || $action === 'reject') {
        // Supprimer l'ami ou refuser la demande
        $stmt = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$my_id, $target_id, $target_id, $my_id]);
    }
}

// Retour intelligent (là d'où l'on vient)
$redirect = "friends.php";
if (isset($_SERVER['HTTP_REFERER'])) {
    $redirect = $_SERVER['HTTP_REFERER'];
}
header("Location: " . $redirect);
exit;