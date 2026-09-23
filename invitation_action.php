<?php
require "db.php";
require "auth.php";

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $invite_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // On récupère l'invitation pour vérifier qu'elle t'est bien destinée
    $stmt = $pdo->prepare("SELECT * FROM group_invitations WHERE id = ? AND invitee_id = ?");
    $stmt->execute([$invite_id, $user_id]);
    $invite = $stmt->fetch();

    if ($invite) {
        if ($action === 'accept') {
            // Ajout dans le groupe
            $insert = $pdo->prepare("INSERT IGNORE INTO group_users (user_id, group_id) VALUES (?, ?)");
            $insert->execute([$user_id, $invite['group_id']]);
        }
        // Qu'on accepte ou qu'on refuse, on supprime la notification
        $del = $pdo->prepare("DELETE FROM group_invitations WHERE id = ?");
        $del->execute([$invite_id]);
    }
}
header("Location: notifications.php");
exit;