<?php
require "db.php";
require "auth.php";

// 1. On récupère TOUS les films notés par l'utilisateur (Pour le mixeur)
$stmt = $pdo->prepare("
    SELECT DISTINCT f.title, r.rating 
    FROM ratings r 
    JOIN films f ON r.film_id = f.id 
    WHERE r.user_id = ? 
    ORDER BY f.title ASC
");
$stmt->execute([$_SESSION['user_id']]);
$allRatedFilms = $stmt->fetchAll();

// 2. On récupère tous les films DÉJÀ VUS pour les exclure des suggestions
$stmtSeen = $pdo->prepare("
    SELECT DISTINCT f.title 
    FROM films f
    JOIN group_users gu ON f.group_id = gu.group_id
    WHERE gu.user_id = ? AND f.seen = 1
    UNION
    SELECT DISTINCT f.title
    FROM ratings r
    JOIN films f ON r.film_id = f.id
    WHERE r.user_id = ?
");
$stmtSeen->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$seenFilms = $stmtSeen->fetchAll(PDO::FETCH_COLUMN);
// On met tout en minuscules pour que la comparaison avec l'API soit parfaite
$seenFilmsLower = array_map('strtolower', $seenFilms);

require "header.php";
?>

<div class="container">
    <div class="dashboard-header" style="flex-direction: column; text-align: center;">
        <h1 style="color: var(--primary);">💡 Que regarder ce soir ?</h1>
        <p style="opacity: 0.7; max-width: 600px;">Mixez vos films vus pour obtenir des recommandations sur-mesure (les films déjà vus sont automatiquement masqués).</p>
    </div>

    <div class="form-card" style="margin: 0 auto 30px auto; max-width: 800px; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h4 style="margin: 0; color: var(--primary);">✨ Mixeur de recommandations (<span id="selectedCount">0</span>/3)</h4>
            <input type="text" id="filterMyFilms" placeholder="Filtrer mes films..." style="margin: 0; width: 200px; padding: 5px 10px; font-size: 0.8rem;">
        </div>
        
        <div id="likedTagsContainer" style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; max-height: 200px; overflow-y: auto; padding: 15px; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--glass-border); box-shadow: inset 0 2px 8px rgba(0,0,0,0.05);">
            <?php foreach($allRatedFilms as $f): ?>
                <button class="btn btn-small liked-tag" 
                        data-title="<?= htmlspecialchars(strtolower($f['title'])) ?>"
                        style="background: var(--input-bg); border-color: transparent; transition: 0.2s;" 
                        onclick="toggleLikedFilm(this, '<?= htmlspecialchars(addslashes($f['title'])) ?>')">
                    <?= htmlspecialchars($f['title']) ?> <small style="opacity: 0.5;">(<?= $f['rating'] ?>★)</small>
                </button>
            <?php endforeach; ?>
        </div>
        
        <p style="font-size: 0.75rem; opacity: 0.5; margin-top: 10px; text-align: center;">Cliquez sur 1 à 3 films pour générer des suggestions similaires.</p>
    </div>

    <div class="form-card" style="margin: 0 auto 20px auto; max-width: 800px; padding: 20px;">
    <form action="javascript:void(0);" style="display: flex; gap: 10px; width: 100%; margin: 0;">
        <input type="search" id="searchInput" placeholder="🔍 Rechercher un nouveau film (ex: Inception...)" style="margin: 0; font-size: 1.1rem; padding: 15px; flex: 1; border-radius: 10px; border: 1px solid var(--glass-border); background: var(--input-bg); color: var(--text-main);">
        <button type="submit" class="btn primary" style="padding: 0 20px;">Rechercher</button>
    </form>
</div>

    <div class="filters-container" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 30px;">
        <select id="genreSelect" class="sort-select" style="padding: 12px 20px; font-size: 1rem;">
            <option value="">🎭 Tous les genres</option>
            <option value="28">Action</option><option value="12">Aventure</option><option value="16">Animation</option>
            <option value="35">Comédie</option><option value="80">Crime</option><option value="18">Drame</option>
            <option value="10751">Familial</option><option value="14">Fantastique</option><option value="36">Histoire</option>
            <option value="27">Horreur</option><option value="10402">Musique</option><option value="9648">Mystère</option>
            <option value="10749">Romance</option><option value="878">Sci-Fi</option><option value="53">Thriller</option>
            <option value="10752">Guerre</option><option value="37">Western</option>
        </select>

        <select id="sortSelect" class="sort-select" style="padding: 12px 20px; font-size: 1rem;">
            <option value="popularity.desc">🔥 Tendances actuelles</option>
            <option value="vote_count.desc">👑 Grands classiques</option>
            <option value="vote_average.desc">⭐ Mieux notés</option>
            <option value="revenue.desc">💰 Plus gros succès</option>
        </select>

        <button onclick="applyFilters()" class="btn primary" style="font-size: 1rem;">Filtrer</button>
    </div>

    <hr style="border-color: var(--glass-border); margin: 30px 0;">
    <h3 id="sectionTitle" style="margin-bottom: 20px; text-align: center; font-size: 1.5rem;">🔥 Tendances actuelles</h3>
    <div id="movieGrid" class="film-grid"></div>

    <div style="text-align: center; margin: 40px 0;">
        <button id="loadMoreBtn" onclick="loadMore()" class="btn big" style="display: none; background: var(--input-bg); border-color: var(--primary); width: 100%; max-width: 300px; margin: 0 auto;">
            Voir plus de films 🍿
        </button>
    </div>
</div>

<script>
const grid = document.getElementById('movieGrid');
const sectionTitle = document.getElementById('sectionTitle');
const searchInput = document.getElementById('searchInput');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const filterMyFilms = document.getElementById('filterMyFilms');

let currentMode = 'discover'; 
let currentQuery = '';
let currentPage = 1;
let selectedLikedFilms = [];

// Liste d'exclusion pour ne pas proposer des films déjà vus
const alreadySeenFilms = <?= json_encode($seenFilmsLower) ?>;

function filterUnseen(movies) {
    return movies.filter(movie => !alreadySeenFilms.includes(movie.title.toLowerCase()));
}

// --- FILTRER SES PROPRES FILMS DANS LE MIXEUR ---
filterMyFilms.addEventListener('input', (e) => {
    const val = e.target.value.toLowerCase();
    document.querySelectorAll('.liked-tag').forEach(tag => {
        const title = tag.getAttribute('data-title');
        tag.style.display = title.includes(val) ? 'inline-block' : 'none';
    });
});

function createMovieCard(movie) {
    if (!movie.poster_path) return ''; 
    const poster = `https://image.tmdb.org/t/p/w500${movie.poster_path}`;
    const year = movie.release_date ? movie.release_date.substring(0, 4) : 'N/A';
    
    return `
        <div class="film-card" style="animation: popIn 0.4s ease-out;">
            <div class="card-poster-wrapper">
                <a href="preview.php?id=${movie.id}" class="details-link">
                    <img src="${poster}" alt="${movie.title}" loading="lazy">
                </a>
            </div>
            <div class="film-info">
                <h3>
                    <a href="preview.php?id=${movie.id}" style="text-decoration: none; color: inherit;">
                        ${movie.title}
                    </a> 
                    <span style="opacity:0.5; font-weight:normal; font-size:0.8em;">(${year})</span>
                </h3>
                <span style="color: var(--primary); font-weight: bold;">⭐ ${movie.vote_average.toFixed(1)}/10</span>
            </div>
        </div>
    `;
}

function toggleLikedFilm(btn, title) {
    if (selectedLikedFilms.includes(title)) {
        selectedLikedFilms = selectedLikedFilms.filter(f => f !== title);
        btn.style.background = 'var(--input-bg)';
        btn.style.color = 'var(--text-main)';
    } else {
        if(selectedLikedFilms.length >= 3) return alert("Maximum 3 films !");
        selectedLikedFilms.push(title);
        btn.style.background = 'var(--primary)';
        btn.style.color = '#000';
    }
    document.getElementById('selectedCount').innerText = selectedLikedFilms.length;
    
    // Vider la recherche manuelle si on utilise les tags
    searchInput.value = '';

    if (selectedLikedFilms.length > 0) {
        currentMode = 'recommendations';
        currentPage = 1;
        sectionTitle.innerText = "✨ Mixage en cours...";
        grid.innerHTML = "<p style='text-align:center; width:100%; opacity:0.5;'>Recherche de pépites... ⏳</p>";
        fetchRecommendations(currentPage);
    } else {
        applyFilters();
    }
}

async function fetchRecommendations(page) {
    try {
        let allRecs = [];
        for (let title of selectedLikedFilms) {
            const s = await fetch(`https://api.themoviedb.org/3/search/movie?api_key=${TMDB_API_KEY}&language=fr-FR&query=${encodeURIComponent(title)}`);
            const sData = await s.json();
            if (sData.results.length > 0) {
                const r = await fetch(`https://api.themoviedb.org/3/movie/${sData.results[0].id}/recommendations?api_key=${TMDB_API_KEY}&language=fr-FR&page=${page}`);
                const rData = await r.json();
                allRecs = allRecs.concat(rData.results);
            }
        }
        
        // Supprimer doublons de l'API
        const unique = Array.from(new Map(allRecs.map(m => [m.id, m])).values());
        
        // Supprimer les films déjà vus et mélanger
        const unseenOnly = filterUnseen(unique).sort(() => Math.random() - 0.5);
        
        if (page === 1) grid.innerHTML = "";
        grid.innerHTML += unseenOnly.slice(0, 18).map(createMovieCard).join('');
        loadMoreBtn.style.display = unseenOnly.length > 0 ? 'block' : 'none';
        sectionTitle.innerText = "✨ Mixage : " + selectedLikedFilms.join(" + ");
    } catch (e) {
        console.error(e);
    }
}

async function fetchDiscover(page) {
    const genre = document.getElementById('genreSelect').value;
    const sort = document.getElementById('sortSelect').value;
    let url = `https://api.themoviedb.org/3/discover/movie?api_key=${TMDB_API_KEY}&language=fr-FR&page=${page}&sort_by=${sort}&vote_count.gte=300`;
    if (genre) url += `&with_genres=${genre}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        
        // Filtrer les films vus
        const unseenData = filterUnseen(data.results);
        
        grid.innerHTML += unseenData.map(createMovieCard).join('');
        loadMoreBtn.style.display = data.page < data.total_pages ? 'block' : 'none';
    } catch (e) {
        console.error(e);
    }
}

function applyFilters() {
    // Décocher les tags si on utilise les filtres
    selectedLikedFilms = [];
    document.getElementById('selectedCount').innerText = 0;
    document.querySelectorAll('.liked-tag').forEach(btn => {
        btn.style.background = 'var(--input-bg)';
        btn.style.color = 'var(--text-main)';
    });
    searchInput.value = '';

    currentMode = 'discover'; 
    currentPage = 1; 
    grid.innerHTML = "";
    
    const genreText = document.getElementById('genreSelect').options[document.getElementById('genreSelect').selectedIndex].text;
    if (document.getElementById('genreSelect').value) {
        sectionTitle.innerText = `🍿 Découverte : ${genreText}`;
    } else {
        sectionTitle.innerText = `🔥 Tendances actuelles`;
    }

    fetchDiscover(currentPage);
}

// --- RECHERCHE MANUELLE ---
// (On ne filtre pas les films vus ici, pour permettre de chercher un film précis et l'ajouter à un autre bocal)
searchInput.addEventListener('input', async (e) => {
    const query = e.target.value.trim();
    if (query.length < 3) {
        if (query.length === 0) applyFilters();
        return;
    }
    
    // Décocher les tags
    selectedLikedFilms = [];
    document.getElementById('selectedCount').innerText = 0;
    document.querySelectorAll('.liked-tag').forEach(btn => {
        btn.style.background = 'var(--input-bg)';
        btn.style.color = 'var(--text-main)';
    });

    currentMode = 'search';
    currentQuery = query;
    currentPage = 1;
    sectionTitle.innerText = `🔍 Résultats pour "${query}"`;
    grid.innerHTML = "";
    
    await fetchSearch(currentQuery, currentPage);
});

async function fetchSearch(query, page) {
    try {
        const res = await fetch(`https://api.themoviedb.org/3/search/movie?api_key=${TMDB_API_KEY}&language=fr-FR&query=${encodeURIComponent(query)}&page=${page}`);
        const data = await res.json();
        grid.innerHTML += data.results.map(createMovieCard).join('');
        loadMoreBtn.style.display = data.page < data.total_pages ? 'block' : 'none';
    } catch (e) {
        console.error(e);
    }
}

async function loadMore() {
    currentPage++;
    const oldText = loadMoreBtn.innerText;
    loadMoreBtn.innerText = "Chargement... ⏳";
    
    if (currentMode === 'discover') await fetchDiscover(currentPage);
    else if (currentMode === 'recommendations') await fetchRecommendations(currentPage);
    else if (currentMode === 'search') await fetchSearch(currentQuery, currentPage);
    
    loadMoreBtn.innerText = oldText;
}
document.querySelector('.form-card form').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('searchInput').blur(); 
});
// Démarrage initial
applyFilters();

</script>

<?php require "footer.php"; ?>