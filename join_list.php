<?php
require "db.php";
require "auth.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['invite_code'])) {
    $code = strtoupper(trim($_POST['invite_code'])); // On force en majuscules pour correspondre au code généré

    $stmt = $pdo->prepare("SELECT id FROM groups_table WHERE invite_code = ?");
    $stmt->execute([$code]);
    $groupId = $stmt->fetchColumn();

    if ($groupId) {
        // Ajouter l'utilisateur au groupe
        $stmt = $pdo->prepare("INSERT IGNORE INTO group_users (user_id, group_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $groupId]);
        
        // On définit ce bocal comme actif
        $_SESSION['active_group_id'] = $groupId;
        
        header("Location: dashboard.php?group_id=" . $groupId);
        exit;
    } else {
        // Redirection avec un petit message d'erreur
        header("Location: lists.php?error=invalid_code");
        exit;
    }
}
header("Location: lists.php");
exit;