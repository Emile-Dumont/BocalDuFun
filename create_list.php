<?php
require "db.php";
require "auth.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['list_name'])) {
    $list_name = $_POST['list_name'];
    // Génère un code unique de 6 caractères
    $invite_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    try {
        $pdo->beginTransaction();

        // 1. Créer le groupe
        $stmt = $pdo->prepare("INSERT INTO groups_table (name, invite_code) VALUES (?, ?)");
        $stmt->execute([$list_name, $invite_code]);
        $new_group_id = $pdo->lastInsertId();

        // 2. Lier l'utilisateur au groupe
        $stmt = $pdo->prepare("INSERT INTO group_users (user_id, group_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $new_group_id]);

        $pdo->commit();
        
        // Redirection vers la nouvelle liste
        header("Location: dashboard.php?group_id=" . $new_group_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la création de la liste.");
    }
}
header("Location: lists.php");
exit;
?>