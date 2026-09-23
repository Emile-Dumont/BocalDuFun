<?php
require "db.php";
require "auth.php";

if (isset($_GET['id']) && isset($_SESSION['active_group_id'])) {
    $stmt = $pdo->prepare("UPDATE films SET seen = 1 WHERE id = ? AND group_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['active_group_id']]);
}

header("Location: dashboard.php");
exit;
?>