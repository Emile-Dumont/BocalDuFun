<?php
require "db.php";
require "auth.php";
$user_id = $_SESSION['user_id'];

// 1. Recherche d'utilisateurs
$search = $_GET['q'] ?? '';
$searchResults = [];
if ($search) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ? LIMIT 10");
    $stmt->execute(["%$search%", $user_id]);
    $searchResults = $stmt->fetchAll();
}

// 2. Récupération des relations (Uniquement Amis acceptés)
$stmt = $pdo->prepare("
    SELECT f.id as friendship_id, f.status, f.user_id as requester_id, u.id as friend_id, u.username 
    FROM friends f
    JOIN users u ON (u.id = f.user_id OR u.id = f.friend_id)
    WHERE (f.user_id = ? OR f.friend_id = ?) AND u.id != ? AND f.status = 'accepted'
");
$stmt->execute([$user_id, $user_id, $user_id]);
$friends = $stmt->fetchAll();

// Fonction pour vérifier le statut pendant la recherche
function getStatus($searched_id, $pdo, $my_id) {
    $stmt = $pdo->prepare("SELECT status, user_id as requester FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->execute([$my_id, $searched_id, $searched_id, $my_id]);
    return $stmt->fetch();
}

require "header.php";
?>

<div class="container" style="max-width: 800px;">
    
    <div class="dashboard-header" style="justify-content: space-between;">
        <h1>🤝 Mes Amis</h1>
        <a href="account.php" class="btn btn-small" style="background: var(--input-bg);">← Retour au profil</a>
    </div>

    <div class="form-card" style="margin: 0 auto 40px auto; text-align: left; max-width: 100%;">
        <h3 style="margin-bottom: 15px;">🔍 Trouver un ami</h3>
        <form method="get" style="display: flex; gap: 10px;">
            <input type="text" name="q" placeholder="Entrez un pseudo..." value="<?= htmlspecialchars($search) ?>" style="margin: 0; flex-grow: 1;">
            <button type="submit" class="btn primary">Chercher</button>
        </form>

        <?php if ($search): ?>
            <div style="margin-top: 20px;">
                <?php if (empty($searchResults)): ?>
                    <p style="opacity: 0.6;">Aucun utilisateur trouvé pour "<?= htmlspecialchars($search) ?>"</p>
                <?php else: ?>
                    <?php foreach ($searchResults as $user): 
                        $rel = getStatus($user['id'], $pdo, $user_id);
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--input-bg); border-radius: 8px; margin-bottom: 5px;">
                            
                            <a href="account.php?id=<?= $user['id'] ?>" style="font-size: 1.1rem; font-weight: bold; color: var(--text-main); text-decoration: none;">
                                <?= htmlspecialchars($user['username']) ?>
                            </a>
                            
                            <div>
                                <?php if (!$rel): ?>
                                    <a href="friend_action.php?action=add&id=<?= $user['id'] ?>" class="btn btn-small primary">➕ Ajouter</a>
                                <?php elseif ($rel['status'] === 'accepted'): ?>
                                    <span style="color: #2ecc71;">✓ Ami</span>
                                <?php elseif ($rel['status'] === 'pending' && $rel['requester'] == $user_id): ?>
                                    <span style="opacity: 0.6;">⏳ Demande envoyée</span>
                                <?php elseif ($rel['status'] === 'pending' && $rel['requester'] != $user_id): ?>
                                    <a href="friend_action.php?action=accept&id=<?= $user['id'] ?>" class="btn btn-small" style="background: #2ecc71; color: white; border: none;">✅ Accepter</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        
        <div class="film-card" style="padding: 20px;">
            <h3 style="margin-bottom: 15px;">👥 Ma Liste (<?= count($friends) ?>)</h3>
            <?php if (empty($friends)): ?>
                <p style="opacity: 0.5;">Vous n'avez pas encore d'amis dans le bocal.</p>
            <?php else: ?>
                <?php foreach ($friends as $friend): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 10px; background: var(--input-bg); border-radius: 8px;">
                        
                        <a href="account.php?id=<?= $friend['friend_id'] ?>" style="font-weight: bold; font-size: 1.1rem; color: var(--text-main); text-decoration: none;">
                            👤 <?= htmlspecialchars($friend['username']) ?>
                        </a>
                        
                        <a href="friend_action.php?action=remove&id=<?= $friend['friend_id'] ?>" 
                           onclick="return confirm('Retirer <?= htmlspecialchars($friend['username']) ?> de vos amis ?');" 
                           style="color: #e74c3c; font-size: 0.8rem; opacity: 0.7;">Retirer</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require "footer.php"; ?>