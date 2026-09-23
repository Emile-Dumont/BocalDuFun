<?php
require "db.php";
require "auth.php";

// Récupérer les listes de l'utilisateur AVEC le nombre de membres
// On ajoute un COUNT() pour savoir si on est seul ou plusieurs (pour l'affichage UX)
$stmt = $pdo->prepare("
    SELECT g.*, 
           (SELECT COUNT(*) FROM group_users gu2 WHERE gu2.group_id = g.id) as member_count
    FROM groups_table g
    JOIN group_users gu ON g.id = gu.group_id
    WHERE gu.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$my_lists = $stmt->fetchAll();

require "header.php";
?>

<div class="container">
    <h1>📁 Mes Listes de Films</h1>
    
    <div class="film-grid">
        <?php foreach ($my_lists as $list): ?>
            <div class="film-card" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid rgba(255,255,255,0.1);">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 15px;">
    
                    <h3 style="margin: 0; text-align: left; font-size: 1.1rem; line-height: 1.3; word-break: break-word; flex-grow: 1;">
                        <?= htmlspecialchars($list['name']) ?>
                    </h3>
                    
                    <?php 
                        // Message différent selon si on est seul ou plusieurs
                        $isAlone = ($list['member_count'] <= 1);
                        $confirmMsg = $isAlone 
                            ? "⚠️ Attention !\\n\\nVous êtes le dernier membre.\\nSi vous quittez, la liste « " . addslashes($list['name']) . " » sera DÉFINITIVEMENT supprimée." 
                            : "Voulez-vous quitter la liste « " . addslashes($list['name']) . " » ?\\n\\nLa liste continuera d'exister pour les autres membres.";
                        
                        $icon = $isAlone ? '🗑' : '🚪'; // Poubelle si seul, Porte si plusieurs
                        $color = $isAlone ? 'btn-delete' : 'btn-leave'; // Classe CSS (on peut réutiliser btn-delete ou en créer une autre)
                    ?>

                    <a href="leave_list.php?id=<?= $list['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" 
                       class="btn-icon <?= $isAlone ? 'btn-delete' : '' ?>" 
                       style="flex-shrink: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: <?= $isAlone ? '' : 'rgba(255,255,255,0.1)' ?>;"
                       onclick="return confirm('<?= $confirmMsg ?>');"
                       title="<?= $isAlone ? 'Supprimer la liste' : 'Quitter la liste' ?>">
                       <?= $icon ?>
                    </a>

                </div>

                <div style="text-align: center; margin-bottom: 20px;">
                    <p style="margin: 10px 0;">
                        <small>Membres : <strong><?= $list['member_count'] ?></strong></small><br>
                        <small>Code : <strong style="font-size: 1.1em; color: #f39c12; letter-spacing:1px;"><?= htmlspecialchars($list['invite_code']) ?></strong></small>
                    </p>
                </div>
                
                <a href="dashboard.php?group_id=<?= $list['id'] ?>" class="btn primary" style="text-decoration: none; display: block; width: 100%; text-align: center;">
                   📂 Ouvrir ce bocal
                </a>
            </div>
        <?php endforeach; ?>

        <div class="film-card" style="padding: 20px; border: 2px dashed #666; background: none; text-align: center;">
            <h3>Nouvelle Liste</h3>
            <form action="create_list.php" method="post">
                <input type="text" name="list_name" placeholder="Nom (ex: Watchlist)" required style="width: 100%;">
                <button class="btn" style="width: 100%;">Créer</button>
            </form>
        </div>

        <div class="film-card" style="padding: 20px; border: 2px dashed #f39c12; background: none; text-align: center;">
            <h3>Rejoindre un ami</h3>
            <form action="join_list.php" method="post">
                <input type="text" name="invite_code" placeholder="Code d'invitation" required style="width: 100%;">
                <button class="btn primary" style="width: 100%;">Rejoindre</button>
            </form>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>