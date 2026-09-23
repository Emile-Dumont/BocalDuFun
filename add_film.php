<?php
require "db.php";
require "auth.php";

$error = null;
$user_id = $_SESSION['user_id'];
$active_group_id = $_SESSION['active_group_id'] ?? null;

if (!$active_group_id) {
    header("Location: lists.php");
    exit;
}

// 1. D'OÙ VIENT L'UTILISATEUR ?
$is_from_discovery = isset($_GET['title']) || isset($_GET['poster']);

// 2. PRÉPARATION DES DONNÉES
$myGroups = [];
if ($is_from_discovery) {
    $stmtGroups = $pdo->prepare("
        SELECT g.id, g.name 
        FROM groups_table g
        JOIN group_users gu ON g.id = gu.group_id
        WHERE gu.user_id = ?
    ");
    $stmtGroups->execute([$user_id]);
    $myGroups = $stmtGroups->fetchAll();
} else {
    $stmtName = $pdo->prepare("SELECT name FROM groups_table WHERE id = ?");
    $stmtName->execute([$active_group_id]);
    $current_group_name = $stmtName->fetchColumn();
}

// 3. TRAITEMENT DU FORMULAIRE
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $poster = trim($_POST["poster"]);
    $tmdb_id = !empty($_POST["tmdb_id"]) ? intval($_POST["tmdb_id"]) : null;
    $genres = !empty($_POST["genres"]) ? trim($_POST["genres"]) : null;
    $runtime = !empty($_POST["runtime"]) ? intval($_POST["runtime"]) : null;
    $providers = !empty($_POST["providers"]) ? trim($_POST["providers"]) : null;
    
    if ($is_from_discovery) {
        $target_groups = $_POST['target_groups'] ?? [];
    } else {
        $target_groups = [$active_group_id];
    }

    if (empty($target_groups)) {
        $error = "Veuillez choisir au moins un bocal !";
    } elseif ($title && $poster) {
        try {
            $already_exists = false;
            $existing_in = [];

            // --- NOUVELLE VÉRIFICATION DES DOUBLONS ---
            foreach ($target_groups as $gid) {
                $check = $pdo->prepare("SELECT g.name FROM films f JOIN groups_table g ON f.group_id = g.id WHERE f.title = ? AND f.group_id = ?");
                $check->execute([$title, $gid]);
                $found = $check->fetch();
                if ($found) {
                    $already_exists = true;
                    $existing_in[] = $found['name'];
                }
            }

            if ($already_exists) {
                if (!$is_from_discovery) {
                    $error = "Ce film est déjà présent dans ce bocal ! 🍿";
                } else {
                    $error = "Ce film existe déjà dans : " . implode(', ', $existing_in);
                }
            } else {
                // Si aucun doublon n'est trouvé, on insère
                $pdo->beginTransaction();
                foreach ($target_groups as $gid) {
                    $stmt = $pdo->prepare("
                    INSERT INTO films (group_id, title, description, poster_url, added_by, tmdb_id, genres, runtime, providers)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([intval($gid), $title, '', $poster, $user_id, $tmdb_id, $genres, $runtime, $providers]);
                }
                $pdo->commit();
                header("Location: dashboard.php");
                exit;
            }
        } catch (Exception $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            $error = "Erreur lors de l'ajout.";
        }
    }
}

require "header.php";
?>

<div class="form-card" style="max-width: 500px;">
    <h2>➕ Ajouter au bocal</h2>
    
    <?php if ($error): ?>
        <div class="error" style="background:rgba(231, 76, 60, 0.2); padding:15px; border-radius:12px; margin-bottom:20px; color:#ff6b6b; text-align:center; border: 1px solid rgba(231, 76, 60, 0.3); animation: shake 0.5s ease-in-out;">
            ⚠️ <strong>Oups !</strong><br><?= $error ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_from_discovery): ?>
        <p style="text-align: center; opacity: 0.7; margin-top: -10px; margin-bottom: 20px;">
            Destination : <strong style="color: var(--primary);"><?= htmlspecialchars($current_group_name) ?></strong>
        </p>
        <div style="text-align: left; margin-bottom: 20px;">
            <label style="display:block; margin-bottom:5px;">🔍 Recherche automatique :</label>
            <input id="search" type="text" placeholder="Taper le nom d'un film..." autocomplete="off" style="border-color: var(--primary);">
            <div id="results"></div>
        </div>
        <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 20px 0;">
    <?php endif; ?>

    <form method="post">
        <!-- NOUVEAU : Champ caché pour stocker l'ID TMDB -->
        <input type="hidden" id="tmdb_id" name="tmdb_id" value="<?= htmlspecialchars($_GET['tmdb_id'] ?? '') ?>">        
        <input type="hidden" id="genres" name="genres" value="">
        <input type="hidden" id="runtime" name="runtime" value="">
        <input type="hidden" id="providers" name="providers" value="">

        <div style="text-align: left; margin-bottom: 15px; <?= $is_from_discovery ? 'opacity: 0.6; pointer-events: none;' : '' ?>">
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Titre du film :</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($_GET['title'] ?? '') ?>" required>
        </div>
        
        <div style="text-align: left; margin-bottom: 15px; <?= $is_from_discovery ? 'opacity: 0.6; pointer-events: none;' : '' ?>">
            <label style="display:block; margin-bottom:5px; font-weight:bold;">Affiche du film (URL) :</label>
            <input type="text" id="poster" name="poster" value="<?= htmlspecialchars($_GET['poster'] ?? '') ?>" required>
            
            <?php if (isset($_GET['poster'])): ?>
                <img src="<?= htmlspecialchars($_GET['poster']) ?>" style="width: 100px; border-radius: 8px; margin-top: 10px; display: block; box-shadow: 0 4px 10px rgba(0,0,0,0.5);">
            <?php else: ?>
                <img id="poster-preview" src="" style="width: 100px; border-radius: 8px; margin-top: 10px; display: none; box-shadow: 0 4px 10px rgba(0,0,0,0.5);">
            <?php endif; ?>
        </div>

        <?php if ($is_from_discovery): ?>
            <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 20px 0;">
            <div style="text-align: left; margin-bottom: 20px;">
                <label style="display:block; margin-bottom:10px; font-weight:bold;">Dans quel(s) bocal(aux) ?</label>
                <div style="display: flex; flex-direction: column; gap: 10px; background: var(--input-bg); padding: 15px; border-radius: 10px;">
                    <?php foreach ($myGroups as $group): ?>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="target_groups[]" value="<?= $group['id'] ?>" 
                                <?= ($active_group_id == $group['id']) ? 'checked' : '' ?>
                                style="width: auto; margin: 0;">
                            <span><?= htmlspecialchars($group['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn primary" style="width: 100%;">Confirmer l'ajout 🎬</button>
        <br><br>
        <a href="javascript:history.back()" style="color: var(--text-muted); font-size: 0.9em;">← Annuler et retour</a>
    </form>
</div>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
</style>

<?php if (!$is_from_discovery): ?>
<script>
const searchInput = document.getElementById("search");
const resultsDiv = document.getElementById("results");

searchInput.addEventListener("input", async () => {
    const query = searchInput.value.trim();
    if (query.length < 3) {
        resultsDiv.innerHTML = "";
        return;
    }

    try {
        const response = await fetch(`https://api.themoviedb.org/3/search/movie?api_key=${TMDB_API_KEY}&language=fr-FR&query=${encodeURIComponent(query)}`);        const data = await response.json();
        resultsDiv.innerHTML = "";

        data.results.slice(0, 5).forEach(movie => {
            const div = document.createElement("div");
            div.style = "padding:10px; border-bottom:1px solid rgba(255,255,255,0.1); cursor:pointer; background:var(--input-bg); margin-bottom:2px; border-radius:5px;";
            div.innerHTML = `<strong>${movie.title}</strong> <small>(${movie.release_date ? movie.release_date.substring(0,4) : '?'})</small>`;
            
            div.onclick = async () => {
                // Remplissage classique
                document.getElementById("title").value = movie.title;
                const posterUrl = movie.poster_path ? "https://image.tmdb.org/t/p/w500" + movie.poster_path : "https://via.placeholder.com/300x450?text=Pas+d+affiche";
                document.getElementById("poster").value = posterUrl;
                document.getElementById("poster-preview").src = posterUrl;
                document.getElementById("poster-preview").style.display = "block";
                document.getElementById("tmdb_id").value = movie.id;
                
                resultsDiv.innerHTML = "<p style='padding:10px; opacity:0.7;'>Aspiration des détails TMDB... 🍿</p>";

                // NOUVEAU : Récupération des détails (durée, genre, plateformes)
                try {
                    const detailRes = await fetch(`https://api.themoviedb.org/3/movie/${movie.id}?api_key=${TMDB_API_KEY}&language=fr-FR`);
                    const detailData = await detailRes.json();
                    
                    document.getElementById("runtime").value = detailData.runtime || 0;
                    document.getElementById("genres").value = detailData.genres ? detailData.genres.map(g => g.name).join(', ') : '';

                    const provRes = await fetch(`https://api.themoviedb.org/3/movie/${movie.id}/watch/providers?api_key=${TMDB_API_KEY}`);
                    const provData = await provRes.json();
                    
                    if (provData.results && provData.results.FR && provData.results.FR.flatrate) {
                        const platforms = provData.results.FR.flatrate.map(p => p.provider_name);
                        document.getElementById("providers").value = platforms.join(', ');
                    } else {
                        document.getElementById("providers").value = '';
                    }
                } catch (err) {
                    console.error("Erreur lors de la récupération des détails", err);
                }

                resultsDiv.innerHTML = "";
                searchInput.value = movie.title;
            };
            resultsDiv.appendChild(div);
        });
    } catch (e) { console.error(e); }
});
</script>
<?php endif; ?>

<?php require "footer.php"; ?>