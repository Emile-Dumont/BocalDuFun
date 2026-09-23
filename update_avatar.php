<?php
require_once "db.php";
require_once "auth.php";

$user_id = $_SESSION['user_id'];
$success = null;
$error = null;

// --- LISTE DES AVATARS GÉNÉRIQUES DISPONIBLES ---
$generic_avatars = ['popcorn.svg', 'clap.svg', 'alien.svg', 'ticket.svg', 'lunettes.svg', 'ninja.svg'];
// 1. GESTION DU CHOIX D'UN AVATAR GÉNÉRIQUE
if (isset($_POST['choice_generic'])) {
    $chosen = $_POST['choice_generic'];
    if (in_array($chosen, $generic_avatars)) {
        // On enregistre le chemin relatif vers le dossier assets
        $new_avatar_value = "../assets/avatars/" . $chosen;
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        if ($stmt->execute([$new_avatar_value, $user_id])) {
            $success = "Avatar mis à jour !";
        }
    }
}

// 2. GESTION DE L'UPLOAD CLASSIQUE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== 4) {
    $file = $_FILES['avatar'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $max_size = 2 * 1024 * 1024;
    $upload_dir = 'uploads/';

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_extensions)) {
        $error = "Format non autorisé (.jpg, .png, .webp).";
    } elseif ($file['size'] > $max_size) {
        $error = "Fichier trop lourd (max 2Mo).";
    }

    if ($error === null) {
        $new_file_name = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
// Remplace la ligne 41 par celle-ci :
if (move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $upload_dir . $new_file_name)) {            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$new_file_name, $user_id]);
            $success = "Photo mise à jour !";
        }
    }
}

require_once "header.php";
?>

<div class="container" style="max-width: 600px;">
    <div class="dashboard-header">
        <h1>🎭 Choisir un avatar</h1>
        <a href="account.php" class="btn btn-small" style="background: var(--input-bg);">← Retour</a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">✅ <?= $success ?></div>
    <?php endif; ?>

    <div class="form-card" style="margin-bottom: 30px;">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.1rem; opacity: 0.8;">Sélectionner un avatar du site :</h3>
        
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px; justify-items: center;">
            <?php foreach($generic_avatars as $img): ?>
                <button type="submit" name="choice_generic" value="<?= $img ?>" style="background: none; border: none; padding: 0; cursor: pointer; transition: transform 0.2s;">
                    <img src="assets/avatars/<?= $img ?>" alt="<?= $img ?>" 
                         style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--glass-border); hover: border-color: var(--primary);">
                </button>
            <?php endforeach; ?>
        </form>
    </div>

    <div class="form-card">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.1rem; opacity: 0.8;">Ou importer votre propre photo :</h3>
        <form action="" method="POST" enctype="multipart/form-data" style="text-align: center;">
            <input type="file" name="avatar" accept="image/*" style="font-size: 0.9rem; margin-bottom: 15px;">
            <button type="submit" class="btn primary" style="width: 100%;">Importer ma photo 📸</button>
        </form>
    </div>
</div>

<style>
    button[name="choice_generic"]:hover { transform: scale(1.1); }
    button[name="choice_generic"]:hover img { border-color: var(--primary); }
</style>

<?php require_once "footer.php"; ?>