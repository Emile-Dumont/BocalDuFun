<?php 
require_once "db.php"; 

$theme = $_COOKIE['site_theme'] ?? 'dark';
$notif_count = 0;
$my_avatar_url = "uploads/default_avatar.png"; // Image par défaut

if (isset($_SESSION['user_id'])) {
    // 1. Compte les notifications
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE friend_id = ? AND status = 'pending'");
    $stmt1->execute([$_SESSION['user_id']]);
    $notif_count += $stmt1->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM group_invitations WHERE invitee_id = ?");
    $stmt2->execute([$_SESSION['user_id']]);
    $notif_count += $stmt2->fetchColumn();
    
    // 2. Récupère l'avatar et calcule le bon chemin
    $stmtAvatar = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmtAvatar->execute([$_SESSION['user_id']]);
    $avatar_db = $stmtAvatar->fetchColumn();

    if ($avatar_db) {
        // Si ça commence par ../assets, on enlève le ../ pour le HTML
        if (strpos($avatar_db, '../assets/') === 0) {
            $my_avatar_url = str_replace('../', '', $avatar_db);
        } else {
            $my_avatar_url = "uploads/" . $avatar_db;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bocal.app 🍿</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍿</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
	<link rel="stylesheet" href="style.css?v=PROFIL">
    <link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#e74c3c">
<script>const TMDB_API_KEY = "<?= TMDB_API_KEY ?>";</script>
</head>

<body data-theme="<?= htmlspecialchars($theme) ?>">

<header class="topbar">
    <div class="container topbar-content">
        <div class="logo">
            <a href="index.php">🍿 Bocal<span style="color:var(--primary)">.app</span></a>
        </div>

        <nav class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="lists.php">Mes Listes</a>
                <a href="discovery.php">💡 Découverte</a>
                
                <a href="notifications.php" style="position: relative; padding-right: 15px;">
                    🔔 Notifs
                    <?php if ($notif_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: 0px; background: #e74c3c; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; font-weight: bold;">
                            <?= $notif_count ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <a href="account.php" class="profile-link" style="display: flex; align-items: center; justify-content: center;">
                    <img src="<?= htmlspecialchars($my_avatar_url) ?>" alt="Moi" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                </a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php" class="btn-small primary">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main-content">