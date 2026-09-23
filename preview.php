<?php
require "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// On récupère l'ID qui vient de TMDB (ex: Inception = 27205)
$tmdb_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$tmdb_id) {
    header("Location: discovery.php");
    exit;
}

// =========================================================
// NOUVEAUTÉ : GESTION DES NOTES ET DES AMIS (Le pont BDD/TMDB)
// =========================================================
$myRating = false;
$friendsRatings = [];

if ($isLoggedIn) {
    // 1. On cherche si ce film existe dans TA base de données (en utilisant le tmdb_id)
    $stmtFindFilm = $pdo->prepare("SELECT id FROM films WHERE tmdb_id = ?");
    $stmtFindFilm->execute([$tmdb_id]);
    $internal_film_id = $stmtFindFilm->fetchColumn();

    // S'il existe dans ta base, ça veut dire que quelqu'un l'a ajouté ou noté !
    if ($internal_film_id) {
        
        // 2. Récupérer MA note pour ce film
        $stmtMyRating = $pdo->prepare("SELECT rating FROM ratings WHERE user_id = ? AND film_id = ?");
        $stmtMyRating->execute([$user_id, $internal_film_id]);
        $myRating = $stmtMyRating->fetchColumn();

        // 3. Récupérer les notes de mes amis
        $sqlFriendsRatings = "
            SELECT r.rating, u.username, u.avatar, u.id as friend_id
            FROM ratings r
            JOIN users u ON r.user_id = u.id
            JOIN friends f ON (
                (f.user_id = ? AND f.friend_id = u.id) OR 
                (f.friend_id = ? AND f.user_id = u.id)
            )
            WHERE r.film_id = ? AND f.status = 'accepted'
            ORDER BY r.rating DESC
        ";
        $stmtFriends = $pdo->prepare($sqlFriendsRatings);
        $stmtFriends->execute([$user_id, $user_id, $internal_film_id]);
        $friendsRatings = $stmtFriends->fetchAll();
    }
}

// Fonction pour gérer l'affichage propre des avatars
function getAvatarUrl($avatar_db) {
    $avatar_db = $avatar_db ?: 'default_avatar.png';
    return (strpos($avatar_db, '../assets/') === 0) ? str_replace('../', '', $avatar_db) : "uploads/" . $avatar_db;
}

require "header.php";
?>

<div class="container" style="max-width: 900px;">
    <a href="javascript:history.back()" class="btn-back">← Retour</a>

    <div id="movie-preview-content" class="film-details-container" style="display: flex; gap: 40px; flex-wrap: wrap;">
        <p style="text-align:center; width:100%; opacity:0.5;">Chargement de la fiche technique... 🍿</p>
    </div>
</div>

<script>
// =========================================================
// PARTIE JAVASCRIPT : CHARGEMENT DES INFOS DEPUIS TMDB
// =========================================================
const movieId = <?= $tmdb_id ?>;
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

// Variables PHP passées au JS pour l'affichage des notes
const myRatingHtml = `
    <div style="margin-top: 30px; background: var(--input-bg); padding: 20px; border-radius: 15px; border: 1px solid var(--glass-border);">
        <h3 style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-top: 0;">Ma note sur Bocal.app</h3>
        <?php if ($myRating !== false): ?>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 2.5rem; color: var(--primary); font-weight: bold; line-height: 1; text-shadow: 0 0 15px rgba(243, 156, 18, 0.3);">
                    <?= floatval($myRating) ?>
                </span>
                <span style="font-size: 1.5rem; color: var(--text-muted);">/5 ★</span>            
            </div>
        <?php else: ?>
            <p style="margin: 0; opacity: 0.7; font-style: italic;">Vous n'avez pas encore noté ce film.</p>
        <?php endif; ?>
    </div>
`;

const friendsRatingsHtml = `
    <div style="margin-top: 20px;">
        <h3 style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Notes de mes amis</h3>
        <?php if (!$isLoggedIn): ?>
            <p style="opacity: 0.5;">Connectez-vous pour voir les notes de vos amis.</p>
        <?php elseif (empty($friendsRatings)): ?>
            <p style="opacity: 0.5; font-style: italic;">Aucun ami n'a noté ce film.</p>
        <?php else: ?>
            <div class="ratings-list" style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($friendsRatings as $fr): ?>
                    <a href="account.php?id=<?= $fr['friend_id'] ?>" style="display: flex; align-items: center; gap: 15px; background: var(--input-bg); padding: 10px 15px; border-radius: 10px; text-decoration: none; color: inherit; transition: 0.2s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='transparent'">
                        <img src="<?= htmlspecialchars(getAvatarUrl($fr['avatar'])) ?>" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                        <span style="flex: 1; font-weight: 600;"><?= htmlspecialchars($fr['username']) ?></span>
                        <div style="color: var(--primary); font-weight: bold; background: var(--card-solid-bg); padding: 5px 12px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <?= floatval($fr['rating']) ?> ★
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
`;


async function loadMovieDetails() {
    try {
        const res = await fetch(`https://api.themoviedb.org/3/movie/${movieId}?api_key=${TMDB_API_KEY}&language=fr-FR`);
        const movie = await res.json();

        const credRes = await fetch(`https://api.themoviedb.org/3/movie/${movieId}/credits?api_key=${TMDB_API_KEY}&language=fr-FR`);
        const credits = await credRes.json();

        // 🍿 NOUVEAU : Récupération des plateformes de streaming (France)
        const provRes = await fetch(`https://api.themoviedb.org/3/movie/${movieId}/watch/providers?api_key=${TMDB_API_KEY}`);
        const provData = await provRes.json();
        let streamingHtml = '';

        if (provData.results && provData.results.FR && provData.results.FR.flatrate) {
            const platforms = provData.results.FR.flatrate;
            // On boucle sur chaque plateforme pour générer son logo
            const logos = platforms.map(p => `
                <img src="https://image.tmdb.org/t/p/w45${p.logo_path}" 
                     alt="${p.provider_name}" 
                     title="Disponible sur ${p.provider_name}" 
                     style="width: 45px; height: 45px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: transform 0.2s;" 
                     onmouseover="this.style.transform='scale(1.1)'" 
                     onmouseout="this.style.transform='scale(1)'">
            `).join('');
            
            streamingHtml = `
                <div style="margin-top: 25px; background: var(--card-solid-bg); padding: 15px; border-radius: 15px; border: 1px solid var(--glass-border);">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        📺 Disponible en streaming
                    </h3>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        ${logos}
                    </div>
                </div>
            `;
        } else {
            streamingHtml = `
                <div style="margin-top: 25px; padding: 15px; border-radius: 15px; border: 1px dashed var(--glass-border); background: var(--input-bg);">
                    <p style="margin: 0; font-size: 0.95rem; opacity: 0.7; font-style: italic; text-align: center;">
                        🚫 Ce film n'est actuellement disponible sur aucune plateforme de streaming en France.
                    </p>
                </div>
            `;
        }

        const poster = movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : 'https://via.placeholder.com/300x450?text=Pas+d+affiche';
        const year = movie.release_date ? movie.release_date.substring(0, 4) : 'N/A';
        const runtime = movie.runtime ? `${Math.floor(movie.runtime / 60)}h ${movie.runtime % 60}min` : 'N/A';
        const genres = movie.genres.map(g => g.name).join(', ');
        
        const director = credits.crew.find(c => c.job === 'Director');
        const directorName = director ? director.name : 'Inconnu';
        const cast = credits.cast.slice(0, 5).map(a => a.name).join(', ');

        const addUrl = `add_film.php?title=${encodeURIComponent(movie.title)}&poster=${encodeURIComponent(poster)}&tmdb_id=${movieId}`;

        let actionButton = '';
        if (isLoggedIn) {
            actionButton = `<a href="${addUrl}" class="btn primary big" style="width: 100%; margin-top: 20px;">➕ Ajouter au bocal</a>`;
        } else {
            actionButton = `<a href="login.php" class="btn big" style="width: 100%; margin-top: 20px; background: var(--input-bg);">Connectez-vous pour ajouter</a>`;
        }

        document.getElementById('movie-preview-content').innerHTML = `
            <div class="details-poster" style="flex: 0 0 300px; width: 100%;">
                <img src="${poster}" alt="${movie.title}" style="width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                ${actionButton}
            </div>
            
            <div class="details-content" style="flex: 1; min-width: 300px;">
                <h1 style="margin-bottom: 5px; color: var(--primary); font-size: 2.2rem;">${movie.title} <span style="opacity:0.5; font-size:0.6em; color: var(--text-main);">(${year})</span></h1>
                
                <p style="opacity: 0.7; font-size: 0.95rem; margin-top: 5px;">Moyenne globale (TMDB) : ⭐ ${movie.vote_average.toFixed(1)}/10</p>
                
                <div style="display:flex; gap: 10px; margin: 15px 0; font-size: 0.85rem; flex-wrap: wrap;">
                    <span style="background: var(--input-bg); padding: 6px 12px; border-radius: 20px; font-weight: bold;">⏱️ ${runtime}</span>
                    <span style="background: var(--input-bg); padding: 6px 12px; border-radius: 20px; font-weight: bold;">🎭 ${genres}</span>
                </div>

                <hr class="divider" style="border: 0; border-top: 1px solid var(--glass-border); margin: 25px 0;">

                <h3 style="margin-bottom: 10px; font-size: 1.2rem;">Synopsis</h3>
                <p style="line-height: 1.6; opacity: 0.85; font-size: 1.05rem;">${movie.overview || "Aucun synopsis disponible en français."}</p>

                <p style="margin-top: 20px; opacity: 0.9;"><strong>🎬 Réalisateur :</strong> ${directorName}</p>
                <p style="opacity: 0.9;"><strong>👥 Casting :</strong> ${cast}</p>

                ${streamingHtml}

                ${myRatingHtml}
                ${friendsRatingsHtml}

            </div>
        `;
    } catch (e) {
        document.getElementById('movie-preview-content').innerHTML = "<p>Erreur lors du chargement des informations.</p>";
    }
}

loadMovieDetails();
</script>

<?php require "footer.php"; ?>