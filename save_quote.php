<?php
require "db.php";
require "auth.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['film_id'])) {
    $film_id = intval($_POST['film_id']);
    $quote = trim($_POST['quote']);
    $group_id = $_SESSION['active_group_id'];

    // Sécurité groupe
    $stmt = $pdo->prepare("UPDATE films SET quote = ? WHERE id = ? AND group_id = ?");
    $stmt->execute([$quote, $film_id, $group_id]);

    // Redirection intelligente
    if (isset($_POST['from']) && $_POST['from'] === 'details') {
        header("Location: film_details.php?id=" . $film_id);
    } else {
        header("Location: dashboard.php?filter=seen");
    }
} else {
    header("Location: dashboard.php");
}
exit;