<?php
require "db.php";
require "auth.php";

if (isset($_GET['id'])) {
    $film_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // CAS 1 : SUPPRESSION DE LA NOTE
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM ratings WHERE film_id = ? AND user_id = ?");
        $stmt->execute([$film_id, $user_id]);
    } 
    // CAS 2 : AJOUT OU MODIFICATION DE LA NOTE
    elseif (isset($_GET['rating'])) {
        $rating = floatval($_GET['rating']); 
        
        // Sécurité : note entre 0.5 et 5
        if ($rating >= 0.5 && $rating <= 5) {
            $stmt = $pdo->prepare("
                INSERT INTO ratings (film_id, user_id, rating) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE rating = ?
            ");
            $stmt->execute([$film_id, $user_id, $rating, $rating]);
        }
    }
}

// GESTION REDIRECTION
if (isset($_GET['from']) && $_GET['from'] === 'details') {
    header("Location: film_details.php?id=" . $film_id);
} else {
    // Comportement par défaut (Dashboard)
    $redirect = "dashboard.php?filter=seen";
    if (isset($_SERVER['HTTP_REFERER'])) $redirect = $_SERVER['HTTP_REFERER'];
    header("Location: " . $redirect);
}
exit;