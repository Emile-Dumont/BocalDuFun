</main>

<footer class="footer">
    <p>🍿 Bocal.app &copy; <?= date("Y") ?> – Fait avec ❤️ pour les indécis.</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const drawBtn = document.getElementById("drawBtn");
    const roulette = document.getElementById("roulette");

    if (!drawBtn || !roulette) return;

    drawBtn.addEventListener("click", async (e) => {
        e.preventDefault();
        
        // 1. On récupère toutes les affiches
        let allPosters = Array.from(document.querySelectorAll(".film-card img")).map(img => img.src);
        const fallbackPoster = "https://via.placeholder.com/300x450?text=Suspense...";
        
        // On mélange le tableau d'images pour l'animation
        allPosters.sort(() => Math.random() - 0.5); 

        // 2. On ouvre la fenêtre
        roulette.classList.remove("hidden");
        
        // Structure de base
        roulette.innerHTML = `
            <h2 style="color:var(--primary)">🎲 La routourne tourne...</h2>
            <div style="position: relative; width: 200px; height: 300px; margin: 20px auto; overflow: hidden; border-radius: 10px; border: 2px solid #fff;">
                <img id="roulette-img" src="${allPosters[0] || fallbackPoster}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <p>Quel film ce soir ?</p>
        `;

        const imgElement = document.getElementById("roulette-img");
        let interval = null;

        // 3. ANIMATION VISUELLE 
        if (allPosters.length > 0) {
            let i = 0;
            interval = setInterval(() => {
                // On boucle sur le tableau mélangé
                imgElement.src = allPosters[i % allPosters.length];
                i++;
            }, 80); // Change l'image toutes les 80ms
        }

        try {
            // --- NOUVEAUTÉ : LECTURE DES FILTRES ---
            
            // On ferme la petite modale des choix si elle est ouverte
            const filterModal = document.getElementById('filterModal');
            if (filterModal) filterModal.classList.add('hidden');

            // On récupère les valeurs que tu as tapées
            const genre = document.getElementById("filterGenre") ? document.getElementById("filterGenre").value.trim() : "";
            const runtime = document.getElementById("filterRuntime") ? document.getElementById("filterRuntime").value : "";
            const provider = document.getElementById("filterProvider") ? document.getElementById("filterProvider").value.trim() : "";
            
            // On construit la fin de l'URL avec les paramètres
            const params = new URLSearchParams();
            if (genre) params.append("genre", genre);
            if (runtime) params.append("runtime", runtime);
            if (provider) params.append("provider", provider);
            
            // 4. Appel au serveur (avec les filtres envoyés !)
            const response = await fetch("draw.php?" + params.toString());
            
            // ----------------------------------------

            if (!response.ok) throw new Error("Erreur réseau");
            const film = await response.json();

            // On laisse tourner 1.5s pour le suspense
            setTimeout(() => {
                if (interval) clearInterval(interval);

                if (!film) {
                    roulette.innerHTML = `<h2>Oups ! Aucun film ne correspond 😕</h2><button class="btn" onclick="closeRoulette()">Fermer</button>`;
                    return;
                }

                // 5. AFFICHAGE DU GAGNANT
                roulette.innerHTML = `
                    <h2 style="margin-bottom:10px; animation: popIn 0.5s;">🎉 Alors, ça vous tente ?</h2>
                    <a href="preview.php?id=${film.tmdb_id}" style="display: inline-block; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" title="Voir le synopsis">
                        <img src="${film.poster_url}" style="max-width:200px; border-radius:10px; box-shadow:0 0 20px rgba(243, 156, 18, 0.5); border: 2px solid var(--primary);">
                    </a>
                    <h3 style="font-size:1.4rem; margin:15px 0;">
                        <a href="preview.php?id=${film.tmdb_id}" style="text-decoration: none; color: inherit; transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='inherit'">${film.title}</a>
                    </h3>
                    
                    <div style="display:flex; gap:10px; justify-content:center; margin-top:20px; flex-wrap: wrap;">
                        <a href="seen.php?id=${film.id}" class="btn primary">🍿 On regarde !</a>
                        <button class="btn" onclick="document.getElementById('drawBtn').click()">🔄 Relancer</button>
                        <button class="btn" onclick="closeRoulette()" style="background: transparent; border-color: var(--glass-border); color: var(--text-muted);">Fermer</button>
                    </div>
                `;

                // 6. CONFETTIS !
                launchConfetti();

            }, 1500);

        } catch (error) {
            if (interval) clearInterval(interval);
            roulette.innerHTML = `<h2>Erreur 😕</h2><p>Réessayez !</p><button class="btn" onclick="closeRoulette()">Fermer</button>`;
        }
    });
});

function closeRoulette() {
    const roulette = document.getElementById("roulette");
    if (roulette) {
        roulette.classList.add("hidden");
        roulette.innerHTML = ""; 
    }
}

// Fonction Confettis
function launchConfetti() {
    var duration = 3 * 1000;
    var animationEnd = Date.now() + duration;
    var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

    function random(min, max) { return Math.random() * (max - min) + min; }

    var interval = setInterval(function() {
        var timeLeft = animationEnd - Date.now();
        if (timeLeft <= 0) { return clearInterval(interval); }
        var particleCount = 50 * (timeLeft / duration);
        confetti(Object.assign({}, defaults, { particleCount, origin: { x: random(0.1, 0.3), y: Math.random() - 0.2 } }));
        confetti(Object.assign({}, defaults, { particleCount, origin: { x: random(0.7, 0.9), y: Math.random() - 0.2 } }));
    }, 250);
}
</script>

</body>
</html>