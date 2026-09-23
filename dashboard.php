<?php
require "db.php";
require "auth.php";

// 1. Gestion Liste Active
if (isset($_GET['group_id'])) {
    $requested_id = intval($_GET['group_id']);
    $check = $pdo->prepare("SELECT 1 FROM group_users WHERE user_id = ? AND group_id = ?");
    $check->execute([$_SESSION['user_id'], $requested_id]);
    if ($check->fetch()) $_SESSION['active_group_id'] = $requested_id;
}
$group_id = $_SESSION['active_group_id'] ?? null;
if (!$group_id) { header("Location: lists.php"); exit; }

// Nom de la liste
$stmtName = $pdo->prepare("SELECT name FROM groups_table WHERE id = ?");
$stmtName->execute([$group_id]);
$current_list_name = $stmtName->fetchColumn() ?: 'Mon Bocal'; 

// --- NOUVEAU : Récupérer les amis qui ne sont PAS encore dans ce bocal ---
$stmtFriends = $pdo->prepare("
    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON (u.id = f.friend_id OR u.id = f.user_id)
    WHERE (f.user_id = ? OR f.friend_id = ?) 
      AND f.status = 'accepted'
      AND u.id != ?
      AND u.id NOT IN (SELECT user_id FROM group_users WHERE group_id = ?)
");
$stmtFriends->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $group_id]);
$invitableFriends = $stmtFriends->fetchAll();
// --------------------------------------------------------------------------

// 2. Filtres & Tri
$filter = $_GET['filter'] ?? 'not_seen'; 
$search = $_GET['q'] ?? '';
$sort   = $_GET['sort'] ?? 'date_desc';

// 3. Stats
$countAll = $pdo->prepare("SELECT COUNT(*) FROM films WHERE group_id = ?");
$countAll->execute([$group_id]);
$totalFilms = $countAll->fetchColumn();
$countSeen = $pdo->prepare("SELECT COUNT(*) FROM films WHERE group_id = ? AND seen = 1");
$countSeen->execute([$group_id]);
$totalSeen = $countSeen->fetchColumn();
$totalToWatch = $totalFilms - $totalSeen;

// 4. Construction de la requête SQL
$orderBy = "f.id DESC";
switch ($sort) {
    case 'date_asc':   $orderBy = "f.id ASC"; break;
    case 'alpha_asc':  $orderBy = "f.title ASC"; break;
    case 'alpha_desc': $orderBy = "f.title DESC"; break;
    case 'rate_desc':  $orderBy = "average_rating DESC"; break;
    case 'rate_asc':   $orderBy = "average_rating ASC"; break;
    default:           $orderBy = "f.id DESC"; break;
}

$sql = "
    SELECT f.*, u.username, AVG(r.rating) as average_rating
    FROM films f 
    JOIN users u ON f.added_by = u.id 
    LEFT JOIN ratings r ON f.id = r.film_id
    WHERE f.group_id = ?
";
$params = [$group_id];

if (!empty($search)) { $sql .= " AND f.title LIKE ?"; $params[] = "%$search%"; }
if ($filter === 'not_seen') $sql .= " AND f.seen = 0";
elseif ($filter === 'seen') $sql .= " AND f.seen = 1";

$sql .= " GROUP BY f.id ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$films = $stmt->fetchAll();

require "header.php";
?>

<div class="container">
    <div class="dashboard-header">
        <h1>🎬 <?= htmlspecialchars($current_list_name) ?></h1>
        <div class="actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="document.getElementById('inviteModal').classList.remove('hidden')" class="btn" style="background: var(--input-bg); border-color: var(--primary);">👥 Inviter</button>
            <a href="add_film.php" class="btn">➕ Ajouter</a>
            <?php if ($totalToWatch > 0): ?>
<button onclick="document.getElementById('filterModal').classList.remove('hidden')" class="btn primary">🎲 Tirer au sort</button>            <?php endif; ?>
        </div>
    </div>

    <div class="toolbar">
        <div class="stats-bar">
            <div class="stat-item"><span class="stat-number"><?= $totalFilms ?></span><span class="stat-label">Total</span></div>
            <div class="stat-item"><span class="stat-number" style="color: #2ecc71;"><?= $totalSeen ?></span><span class="stat-label">Vus</span></div>
            <div class="stat-item"><span class="stat-number" style="color: #f39c12;"><?= $totalToWatch ?></span><span class="stat-label">À voir</span></div>
        </div>
        
        <form method="get" class="search-form" style="display: flex; gap: 10px; flex-wrap: wrap;">
<input type="search" name="q" placeholder="🔍 Rechercher..." value="<?= htmlspecialchars($search) ?>" style="flex-grow: 1; margin: 0;">            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            
            <select name="sort" class="sort-select" onchange="this.form.submit()">
                <option value="date_desc" <?= $sort == 'date_desc' ? 'selected' : '' ?>>📅 Ajout récent</option>
                <option value="date_asc" <?= $sort == 'date_asc' ? 'selected' : '' ?>>📅 Ajout ancien</option>
                <option value="alpha_asc" <?= $sort == 'alpha_asc' ? 'selected' : '' ?>>🔤 A - Z</option>
                <option value="alpha_desc" <?= $sort == 'alpha_desc' ? 'selected' : '' ?>>🔤 Z - A</option>
                <option value="rate_desc" <?= $sort == 'rate_desc' ? 'selected' : '' ?>>⭐ Meilleure note</option>
                <option value="rate_asc" <?= $sort == 'rate_asc' ? 'selected' : '' ?>>⭐ Pire note</option>
            </select>
        </form>

        <div class="filters">
            <a href="?filter=not_seen&q=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="<?= $filter === 'not_seen' ? 'active' : '' ?>">À voir</a>
            <a href="?filter=seen&q=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="<?= $filter === 'seen' ? 'active' : '' ?>">Vus</a>
            <a href="?filter=all&q=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="<?= $filter === 'all' ? 'active' : '' ?>">Tous</a>
        </div>
    </div>

    <div class="film-grid">
        <?php if (empty($films)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; opacity: 0.6;">
                <h3>Aucun film trouvé 🍿</h3>
            </div>
        <?php else: ?>
            <?php foreach ($films as $film): ?>
                
                <div class="film-card <?= ($film['seen'] && $filter === 'all') ? 'seen-card' : '' ?>">
                    <div class="card-poster-wrapper">
                        <?php $link = $film['seen'] ? "film_details.php?id=".$film['id'] : "preview.php?id=".$film['tmdb_id']; ?>
                        <a href="<?= $link ?>" class="details-link">
                            <img src="<?= htmlspecialchars($film["poster_url"]) ?: 'https://via.placeholder.com/300x450?text=No+Poster' ?>" alt="Poster" loading="lazy">
                            <span class="overlay-info">👁️ Voir détails</span>
                        </a>
                    </div>

                    <div class="film-info">
                        <h3><a href="<?= $link ?>"><?= htmlspecialchars($film["title"]) ?></a></h3>

                        <?php if ($film['seen'] && $film['average_rating'] > 0): ?>
                            <div style="display: inline-block; background: rgba(243, 156, 18, 0.2); color: #f39c12; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; margin-bottom: 5px;">
                                ⭐ <?= number_format($film['average_rating'], 1) ?>/5
                            </div>
                        <?php endif; ?>

                        <span>Par <?= htmlspecialchars($film["username"]) ?></span>

                        <div class="card-actions">
                            <a href="toggle_seen.php?id=<?= $film['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon <?= $film['seen'] ? 'btn-uncheck' : 'btn-check' ?>" title="<?= $film['seen'] ? 'Remettre dans le bocal' : 'Marquer comme vu' ?>">
                                <?= $film['seen'] ? '↩️' : '✅' ?>
                            </a>
                            <?php if ($film['seen']): ?>
                                <a href="film_details.php?id=<?= $film['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon" style="background: rgba(255,255,255,0.1); color: var(--text-main);" title="Détails & Notes">👁️</a>
                            <?php endif; ?>
                            <a href="delete_film.php?id=<?= $film['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" class="btn-icon btn-delete" onclick="return confirm('Supprimer ce film ?');" title="Supprimer">🗑</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="roulette" class="roulette hidden"></div>

    <div id="inviteModal" class="roulette hidden" style="text-align: left; max-width: 400px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: var(--primary); margin: 0;">👥 Inviter un ami</h3>
            <button onclick="document.getElementById('inviteModal').classList.add('hidden')" style="background:none; border:none; color:var(--text-main); font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <?php if (empty($invitableFriends)): ?>
            <p style="opacity: 0.6; text-align: center; font-size: 0.9rem;">
                Tous vos amis sont déjà dans ce bocal, ou vous n'avez pas encore d'amis à inviter.
            </p>
        <?php else: ?>
            <div style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($invitableFriends as $ami): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--input-bg); padding: 10px 15px; border-radius: 10px;">
                        <span style="font-weight: bold;"><?= htmlspecialchars($ami['username']) ?></span>
                        <a href="invite_friend.php?friend_id=<?= $ami['id'] ?>&group_id=<?= $group_id ?>" class="btn btn-small primary">Ajouter</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- NOUVEAU : Fenêtre des filtres pour la roulette -->
    <div id="filterModal" class="roulette hidden" style="text-align: left; max-width: 400px; z-index: 1000;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: var(--primary); margin: 0;">🎯 Les règles de ce soir</h3>
            <button onclick="document.getElementById('filterModal').classList.add('hidden')" style="background:none; border:none; color:var(--text-main); font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">🎭 Genre (Optionnel)</label>
                <input type="text" id="filterGenre" placeholder="ex: Action, Comédie, Horreur..." style="width: 100%; border-color: var(--primary);">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">⏳ Durée max (Optionnel)</label>
                <select id="filterRuntime" style="width: 100%; border-color: var(--primary);">
                    <option value="">Peu importe</option>
                    <option value="90">Moins de 1h30</option>
                    <option value="120">Moins de 2h00</option>
                    <option value="150">Moins de 2h30</option>
                </select>
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">📺 Plateforme (Optionnel)</label>
                <input type="text" id="filterProvider" placeholder="ex: Netflix, Amazon, Canal+..." style="width: 100%; border-color: var(--primary);">
            </div>
            
            <!-- Le VRAI bouton qui lance la roulette a été déplacé ici -->
            <button id="drawBtn" class="btn primary" style="width: 100%; margin-top: 10px;">Lancer la Roulette ! 🎲</button>
        </div>
    </div>
<?php require "footer.php"; ?>