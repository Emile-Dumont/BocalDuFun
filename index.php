<?php require "header.php"; ?>

<div class="hero-section">
    <div class="container" style="text-align: center; padding: 60px 20px;">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Ne perdez plus 30min <br>à choisir un film.</h1>
        <p style="font-size: 1.1rem; opacity: 0.8; max-width: 600px; margin: 0 auto 30px auto;">
            Créez votre liste commune, remplissez le bocal, et laissez le hasard décider de votre soirée.
        </p>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="register.php" class="btn primary big">Commencer l'aventure 🚀</a>
                <a href="login.php" class="btn secondary big">Connexion</a>
            </div>
        <?php else: ?>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="lists.php" class="btn primary big">Accéder à mes listes 🎬</a>
                <a href="discovery.php" class="btn big" style="background: var(--input-bg); border: 1px solid var(--primary);">💡 Coin Découverte</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="dashboard-header" style="justify-content: center; margin-top: 40px;">
        <h2>🔥 Tendances du moment</h2>
    </div>
    
    <div id="suggestions" class="film-grid">
        <div style="grid-column:1/-1; text-align:center;">Chargement des tendances...</div>
    </div>

    <div style="text-align: center; margin-top: 40px; margin-bottom: 40px;">
        <a href="discovery.php" class="btn primary big">Explorer plus de films 🚀</a>
    </div>
</div>

<script>

async function loadSuggestions() {
    try {
        const response = await fetch(`https://api.themoviedb.org/3/trending/movie/week?api_key=${TMDB_API_KEY}&language=fr-FR`);        const data = await response.json();
        const container = document.getElementById("suggestions");
        container.innerHTML = "";
        
        data.results.slice(0, 8).forEach(movie => {
            const poster = movie.poster_path ? "https://image.tmdb.org/t/p/w500" + movie.poster_path : "https://via.placeholder.com/300x450";
            
            // NOUVEAU : Au clic, on va voir la fiche technique !
            let clickAction = `window.location.href='preview.php?id=${movie.id}'`;
            
            container.innerHTML += `
                <div class="film-card" onclick="${clickAction}" style="cursor: pointer;" title="Voir la fiche de ${movie.title}">
                    <img src="${poster}">
                    <div class="film-info">
                        <h3>${movie.title}</h3>
                        <span>⭐ ${movie.vote_average.toFixed(1)}/10</span>
                    </div>
                </div>`;
        });
    } catch (e) {
        console.error("Erreur API", e);
    }
}
loadSuggestions();
</script>

<?php require "footer.php"; ?>