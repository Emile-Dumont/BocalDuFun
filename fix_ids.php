<?php
require "db.php";

// Ta clé API (que j'ai récupérée de tes autres fichiers)
$api_key = "3be7f01b1ab6f9c4d21cc7721a69589d";

// 1. On cherche tous les films qui n'ont pas encore de TMDB ID
$stmt = $pdo->query("SELECT id, title FROM films WHERE tmdb_id IS NULL OR tmdb_id = 0");
$films = $stmt->fetchAll();

if (empty($films)) {
    die("<h2>🎉 Tous tes films ont déjà un ID TMDB ! Il n'y a plus rien à réparer.</h2>");
}

echo "<h1>🛠️ Réparation automatique des films en cours... 🍿</h1><ul>";

// 2. On boucle sur chaque film cassé
foreach ($films as $film) {
    // On prépare le titre pour l'URL (ex: "Pulp Fiction" devient "Pulp%20Fiction")
    $title_encoded = urlencode($film['title']);
    $url = "https://api.themoviedb.org/3/search/movie?api_key={$api_key}&language=fr-FR&query={$title_encoded}";

    // 3. On demande à TMDB
    $response = @file_get_contents($url);
    
    if ($response) {
        $data = json_decode($response, true);
        
        // Si TMDB a trouvé le film
        if (!empty($data['results'])) {
            $tmdb_id = $data['results'][0]['id']; // On prend le 1er résultat (le plus pertinent)
            
            // 4. On met à jour ta base de données !
            $update = $pdo->prepare("UPDATE films SET tmdb_id = ? WHERE id = ?");
            $update->execute([$tmdb_id, $film['id']]);
            
            echo "<li style='color: green;'>✅ <b>" . htmlspecialchars($film['title']) . "</b> réparé ! (ID TMDB : $tmdb_id)</li>";
        } else {
            echo "<li style='color: orange;'>⚠️ <b>" . htmlspecialchars($film['title']) . "</b> introuvable sur l'API TMDB... À corriger à la main.</li>";
        }
    } else {
        echo "<li style='color: red;'>❌ Erreur de connexion à l'API pour " . htmlspecialchars($film['title']) . "</li>";
    }

    // Petite pause (0.25 seconde) pour ne pas agresser les serveurs de TMDB
    usleep(250000); 
}

echo "</ul><h2>🚀 C'est terminé ! Retourne sur ton profil, tous les clics fonctionnent !</h2>";
?>