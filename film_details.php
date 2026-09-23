<?php
require "db.php";
require "auth.php";

if (!isset($_GET['id'])) { header("Location: dashboard.php"); exit; }

$film_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// 1. Infos du Film + Ma Note
$stmt = $pdo->prepare("
    SELECT f.*, u.username as added_by_name, r.rating as my_rating
    FROM films f 
    JOIN users u ON f.added_by = u.id 
    LEFT JOIN ratings r ON f.id = r.film_id AND r.user_id = ?
    WHERE f.id = ?
");
$stmt->execute([$user_id, $film_id]);
$film = $stmt->fetch();

if (!$film) { die("Film introuvable."); }

// 2. Récupérer les notes des AUTRES membres
$stmt = $pdo->prepare("
    SELECT r.rating, u.username, u.id as r_user_id
    FROM ratings r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.film_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$film_id]);
$allRatings = $stmt->fetchAll();

require "header.php";
?>

<div class="container" style="max-width: 900px;">
    
    <a href="dashboard.php?filter=seen" class="btn-back">← Retour aux films vus</a>

    <div class="film-details-container">
        <div class="details-poster">
            <img src="<?= htmlspecialchars($film['poster_url']) ?>" alt="Affiche">
        </div>

        <div class="details-content">
            <h1><?= htmlspecialchars($film['title']) ?></h1>
            <p class="added-by">Ajouté par <strong><?= htmlspecialchars($film['added_by_name']) ?></strong></p>
            
            <hr class="divider">

            <div class="quote-section">
                <h3>💬 Citation du film</h3>
                <?php if (!empty($film['quote'])): ?>
                    <blockquote class="film-quote">
                        « <?= htmlspecialchars($film['quote']) ?> »
                    </blockquote>
                <?php else: ?>
                    <p style="opacity: 0.5; font-style: italic;">Pas encore de citation marquante.</p>
                <?php endif; ?>
                
                <button onclick="document.getElementById('quoteModal').classList.remove('hidden')" class="btn-small btn-edit-quote">
                    ✏️ Modifier la citation
                </button>
            </div>

            <hr class="divider">

            <div class="my-rating-section">
                <h3>⭐ Ma Note</h3>
                <div class="rating-stars big-stars">
                    <?php 
                    for ($i = 5.0; $i >= 0.5; $i -= 0.5): 
                        $isHalf = (floor($i) != $i); 
                        $class = $isHalf ? 'star-left' : 'star-right';
                        if (isset($film['my_rating']) && $film['my_rating'] >= $i) $class .= ' filled';
                    ?>
                        <a href="rate_film.php?id=<?= $film['id'] ?>&rating=<?= $i ?>&from=details" class="<?= $class ?>"></a>
                    <?php endfor; ?>
                </div>
                <p style="font-size: 0.8rem; opacity: 0.6; margin-bottom: 10px;">(Cliquez pour noter)</p>
                
                <?php if (isset($film['my_rating'])): ?>
                    <a href="rate_film.php?id=<?= $film['id'] ?>&action=delete&from=details" 
                       class="btn btn-small" 
                       style="background: #e74c3c; color: #fff; border: none; font-size: 0.8rem; text-decoration: none; padding: 5px 15px; display: inline-block;"
                       onclick="return confirm('Es-tu sûr de vouloir supprimer ta note pour ce film ?');">
                        🗑️ Supprimer ma note
                    </a>
                <?php endif; ?>
            </div>

            <hr class="divider">

            <div class="others-ratings">
                <h3>👥 Notes du groupe</h3>
                <?php if (empty($allRatings)): ?>
                    <p style="opacity: 0.5;">Personne n'a encore noté ce film.</p>
                <?php else: ?>
                    <div class="ratings-list">
                        <?php foreach ($allRatings as $rating): ?>
                            <div class="member-rating-row">
                                <div class="avatar-small">
                                    <?= strtoupper(substr($rating['username'], 0, 1)) ?>
                                </div>
                                <div class="member-info">
                                    <span class="member-name"><?= htmlspecialchars($rating['username']) ?></span>
                                    <div class="static-stars">
                                        <span style="color: #f39c12; font-weight: bold;"><?= floatval($rating['rating']) ?></span>/5 ★
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<div id="quoteModal" class="roulette hidden" style="text-align: left;">
    <h3 style="color: var(--primary); margin-top: 0;">💬 Modifier la citation</h3>
    <form action="save_quote.php" method="post">
        <input type="hidden" name="film_id" value="<?= $film['id'] ?>">
        <input type="hidden" name="from" value="details"> <textarea name="quote" rows="4" style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 10px; padding: 10px; font-family: inherit; resize: vertical;" placeholder="Ex: 'Je suis le maître du monde !'"><?= htmlspecialchars($film['quote'] ?? '') ?></textarea>
        
        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button type="submit" class="btn primary" style="width: 100%;">Enregistrer</button>
            <button type="button" class="btn" onclick="document.getElementById('quoteModal').classList.add('hidden')" style="width: 100%;">Annuler</button>
        </div>
    </form>
</div>

<?php require "footer.php"; ?>