<?php
require "db.php";
require "auth.php";

if (
    isset($_GET['id']) && 
    isset($_GET['token']) && 
    hash_equals($_SESSION['csrf_token'], $_GET['token'])
) {
    $group_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    $check = $pdo->prepare("SELECT 1 FROM group_users WHERE group_id = ? AND user_id = ?");
    $check->execute([$group_id, $user_id]);

    if ($check->fetch()) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_users WHERE group_id = ?");
            $stmt->execute([$group_id]);
            $count = $stmt->fetchColumn();

            if ($count > 1) {
                $stmt = $pdo->prepare("DELETE FROM group_users WHERE group_id = ? AND user_id = ?");
                $stmt->execute([$group_id, $user_id]);
                
                if (isset($_SESSION['active_group_id']) && $_SESSION['active_group_id'] == $group_id) {
                    unset($_SESSION['active_group_id']);
                }
            } else {
                $stmt = $pdo->prepare("DELETE FROM ratings WHERE film_id IN (SELECT id FROM films WHERE group_id = ?)");
                $stmt->execute([$group_id]);

                $stmt = $pdo->prepare("DELETE FROM films WHERE group_id = ?");
                $stmt->execute([$group_id]);

                $stmt = $pdo->prepare("DELETE FROM group_users WHERE group_id = ?");
                $stmt->execute([$group_id]);

                $stmt = $pdo->prepare("DELETE FROM groups_table WHERE id = ?");
                $stmt->execute([$group_id]);
                
                if (isset($_SESSION['active_group_id']) && $_SESSION['active_group_id'] == $group_id) {
                    unset($_SESSION['active_group_id']);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

header("Location: lists.php");
exit;