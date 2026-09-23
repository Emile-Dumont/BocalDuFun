<?php
require "db.php";
require "auth.php";

// Changement de thème
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $new_theme = $_POST['theme'];
    setcookie('site_theme', $new_theme, time() + (86400 * 365), "/"); 
    header("Location: account.php"); 
    exit;
}

// 1. DÉTERMINER QUI ON REGARDE
$is_my_profile = !isset($_GET['id']) || $_GET['id'] == $_SESSION['user_id'];
$profile_user_id = $is_my_profile ? $_SESSION['user_id'] : intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_user_id]);
$user = $stmt->fetch();

if (!$user) { die("Cet utilisateur n'existe pas."); }

// 2. RÉCUPÉRER LE BON CHEMIN D'AVATAR
$avatar_db = $user['avatar'] ?: 'default_avatar.png';
if (strpos($avatar_db, '../assets/') === 0) {
    $avatar_url = str_replace('../', '', $avatar_db);
} else {
    $avatar_url = "uploads/" . $avatar_db;
}

// ==========================================
// NOUVEAU : VÉRIFIER LE STATUT D'AMITIÉ
// ==========================================
$friend_status = null;
$requester_id = null;
if (!$is_my_profile) {
    $stmtFriend = $pdo->prepare("SELECT status, user_id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmtFriend->execute([$_SESSION['user_id'], $profile_user_id, $profile_user_id, $_SESSION['user_id']]);
    $friendData = $stmtFriend->fetch();
    if ($friendData) {
        $friend_status = $friendData['status'];
        $requester_id = $friendData['user_id'];
    }
}

// 3. RÉCUPÉRER LES FILMS NOTÉS
$sort = $_GET['sort'] ?? 'rate_desc';
$sql = "SELECT f.id as film_id, f.tmdb_id, f.title, f.poster_url, r.rating FROM ratings r JOIN films f ON r.film_id = f.id WHERE r.user_id = ? ORDER BY r.rating DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$profile_user_id]);
$ratedFilms = $stmt->fetchAll();

require "header.php";
$current_theme = $_COOKIE['site_theme'] ?? 'dark';
?>

<div class="container" style="max-width: 800px;">
    <div class="form-card" style="margin: 0 auto 40px auto; text-align: left;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            
            <div style="display: flex; align-items: center; gap: 20px;">
    <div class="avatar-wrapper" onclick="openAvatarModal('<?= htmlspecialchars($avatar_url) ?>', <?= $is_my_profile ? 'true' : 'false' ?>)">
        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar">
        
    </div>
    
    <div>
        <h2 style="margin: 0; color: var(--text-main);"><?= htmlspecialchars($user['username']) ?></h2>
        <?php if ($is_my_profile): ?>
            <span style="opacity: 0.6; font-size: 0.9rem;"><?= htmlspecialchars($user['email']) ?></span>
        <?php endif; ?>
    </div>
</div>

            <?php if (!$is_my_profile): ?>
                <?php if (!$friend_status): ?>
                    <a href="friend_action.php?action=add&id=<?= $profile_user_id ?>" class="btn primary">➕ Ajouter en ami</a>
                <?php elseif ($friend_status === 'accepted'): ?>
                    <button class="btn" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border-color: #2ecc71; cursor: default;">✅ Ami</button>
                <?php elseif ($friend_status === 'pending' && $requester_id == $_SESSION['user_id']): ?>
                    <button class="btn" style="opacity: 0.7; cursor: default; background: var(--input-bg);">⏳ Demande envoyée</button>
                <?php elseif ($friend_status === 'pending' && $requester_id != $_SESSION['user_id']): ?>
                    <a href="friend_action.php?action=accept&id=<?= $profile_user_id ?>" class="btn primary" style="background: #2ecc71; border: none; color: white;">✅ Accepter la demande</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($is_my_profile): ?>
            <div style="margin-top: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between; border-top: 1px solid var(--glass-border); padding-top: 20px;">
                <form method="post" style="display: flex; gap: 10px;">
                    <button type="submit" name="theme" value="dark" class="btn btn-small <?= $current_theme === 'dark' ? 'primary' : '' ?>">🌙 Sombre</button>
                    <button type="submit" name="theme" value="light" class="btn btn-small <?= $current_theme === 'light' ? 'primary' : '' ?>">🌤️ Doux</button>
                    <button type="submit" name="theme" value="neon" class="btn btn-small <?= $current_theme === 'neon' ? 'primary' : '' ?>">👾 Néon</button>
                </form>
                <div style="display: flex; gap: 10px;">
                    <a href="friends.php" class="btn btn-small" style="background: var(--input-bg); border-color: var(--primary);">👥 Gérer mes Amis</a>
                    <a href="logout.php" class="btn btn-small" style="background: #e74c3c; color: #fff; border: none; font-weight: bold; display: flex; align-items: center; justify-content: center;">Déconnexion</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <h3>🏆 Films notés (<?= count($ratedFilms) ?>)</h3>
    <div class="film-grid" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));">
        <?php foreach ($ratedFilms as $film): ?>
            <div class="film-card" style="border:none; background:none;">
                <div class="card-poster-wrapper" style="border-radius: 8px; overflow: hidden; aspect-ratio: 2/3;">
                    <?php if (!empty($film['tmdb_id'])): ?>
                        <a href="preview.php?id=<?= $film['tmdb_id'] ?>" class="details-link" style="display: block; width: 100%; height: 100%;">
                            <img src="<?= htmlspecialchars($film['poster_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </a>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($film['poster_url']) ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.8;" title="Lien indisponible pour les anciens films">
                    <?php endif; ?>
                </div>
                
                <div style="padding: 10px 0; font-size: 0.85rem;">
                    <strong style="color: var(--primary); display: block; margin-bottom: 5px;">⭐ <?= floatval($film['rating']) ?>/5</strong>
                    <?php if (!empty($film['tmdb_id'])): ?>
                        <a href="preview.php?id=<?= $film['tmdb_id'] ?>" style="text-decoration: none; color: inherit; font-weight: 600;">
                            <?= htmlspecialchars($film['title']) ?>
                        </a>
                    <?php else: ?>
                        <span style="font-weight: 600; opacity: 0.7;"><?= htmlspecialchars($film['title']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div id="avatarModal" class="avatar-modal hidden" onclick="closeAvatarModal()">
    <span class="modal-close">&times;</span>
    <img id="modalImg" class="modal-avatar-img" src="" onclick="event.stopPropagation()">
    
    <div id="modalActions" style="margin-top: 20px;">
        <a href="update_avatar.php" class="btn primary">🖼️ Changer ma photo</a>
    </div>
</div>

<script>
function openAvatarModal(url, isMyProfile) {
    const modal = document.getElementById('avatarModal');
    const modalImg = document.getElementById('modalImg');
    const modalActions = document.getElementById('modalActions');
    
    modalImg.src = url;
    
    // On affiche le bouton modifier seulement si c'est notre profil
    if (isMyProfile) {
        modalActions.classList.remove('hidden');
    } else {
        modalActions.classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
}

function closeAvatarModal() {
    document.getElementById('avatarModal').classList.add('hidden');
}
</script>
<?php require "footer.php"; ?>