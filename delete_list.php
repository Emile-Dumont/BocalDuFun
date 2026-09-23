<?php
require "db.php";
require "auth.php";

// Vérification qu'un ID est bien fourni
if (isset($_GET['id'])) {
    $list_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // SÉCURITÉ : On vérifie que l'utilisateur fait bien partie de cette liste avant de supprimer
    $check = $pdo->prepare("SELECT 1 FROM group_users WHERE group_id = ? AND user_id = ?");
    $check->execute([$list_id, $user_id]);

    if ($check->fetch()) {
        try {
            $pdo->beginTransaction();

            // 1. Supprimer tous les films de la liste
            $stmt = $pdo->prepare("DELETE FROM films WHERE group_id = ?");
            $stmt->execute([$list_id]);

            // 2. Supprimer les membres de la liste (le lien utilisateur-liste)
            $stmt = $pdo->prepare("DELETE FROM group_users WHERE group_id = ?");
            $stmt->execute([$list_id]);

            // 3. Supprimer la liste elle-même
            $stmt = $pdo->prepare("DELETE FROM groups_table WHERE id = ?");
            $stmt->execute([$list_id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // En cas d'erreur, on redirige juste sans rien casser
        }
    }
}

// Retour à la page des listes
header("Location: lists.php");
exit;