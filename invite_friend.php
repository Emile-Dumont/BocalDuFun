<?php
require "db.php";
require "auth.php";

if (isset($_GET['friend_id']) && isset($_GET['group_id'])) {
    $friend_id = intval($_GET['friend_id']);
    $group_id = intval($_GET['group_id']);
    $my_id = $_SESSION['user_id'];

    // 1. Vérif : Vous êtes amis ?
    $checkFriend = $pdo->prepare("SELECT 1 FROM friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'accepted'");
    $checkFriend->execute([$my_id, $friend_id, $friend_id, $my_id]);
    
    if ($checkFriend->fetch()) {
        // 2. Vérif : Tu es bien dans ce groupe ?
        $checkGroup = $pdo->prepare("SELECT 1 FROM group_users WHERE user_id = ? AND group_id = ?");
        $checkGroup->execute([$my_id, $group_id]);
        
        if ($checkGroup->fetch()) {
            // 3. Vérif : Ton ami n'est pas DÉJÀ dans le bocal ?
            $checkAlready = $pdo->prepare("SELECT 1 FROM group_users WHERE user_id = ? AND group_id = ?");
            $checkAlready->execute([$friend_id, $group_id]);
            
            if (!$checkAlready->fetch()) {
                // 4. ON ENVOIE L'INVITATION (dans group_invitations au lieu de group_users)
                $insert = $pdo->prepare("INSERT IGNORE INTO group_invitations (group_id, inviter_id, invitee_id) VALUES (?, ?, ?)");
                $insert->execute([$group_id, $my_id, $friend_id]);
            }
        }
    }
}
header("Location: dashboard.php");
exit;