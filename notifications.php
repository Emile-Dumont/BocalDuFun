<?php
require "db.php";
require "auth.php";
$user_id = $_SESSION['user_id'];

// 1. Récupérer les demandes d'amis reçues
$stmtF = $pdo->prepare("
    SELECT f.user_id as requester_id, u.username 
    FROM friends f 
    JOIN users u ON f.user_id = u.id 
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$stmtF->execute([$user_id]);
$friend_requests = $stmtF->fetchAll();

// 2. Récupérer les invitations aux bocaux
$stmtG = $pdo->prepare("
    SELECT gi.id as invite_id, gi.group_id, g.name as group_name, u.username as inviter_name 
    FROM group_invitations gi 
    JOIN groups_table g ON gi.group_id = g.id 
    JOIN users u ON gi.inviter_id = u.id 
    WHERE gi.invitee_id = ?
");
$stmtG->execute([$user_id]);
$group_invitations = $stmtG->fetchAll();

require "header.php";
?>

<div class="container" style="max-width: 800px;">
    <div class="dashboard-header">
        <h1>🔔 Mes Notifications</h1>
    </div>

    <?php if (empty($friend_requests) && empty($group_invitations)): ?>
        <div class="form-card" style="text-align: center; opacity: 0.6; padding: 50px;">
            <h3>Tout est calme ici.</h3>
            <p>Vous n'avez aucune notification en attente.</p>
        </div>
    <?php endif; ?>

    <div style="display: grid; gap: 20px;">
        
        <?php if (!empty($friend_requests)): ?>
            <div class="film-card" style="padding: 20px;">
                <h3 style="color: var(--primary); margin-bottom: 15px;">🤝 Demandes d'amis</h3>
                <?php foreach ($friend_requests as $req): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--input-bg); border-radius: 8px; margin-bottom: 10px;">
                        <span><strong><?= htmlspecialchars($req['username']) ?></strong> veut être votre ami.</span>
                        <div style="display: flex; gap: 5px;">
                            <a href="friend_action.php?action=accept&id=<?= $req['requester_id'] ?>" class="btn-icon btn-check" style="width: 32px; height: 32px;" title="Accepter">✅</a>
                            <a href="friend_action.php?action=reject&id=<?= $req['requester_id'] ?>" class="btn-icon btn-delete" style="width: 32px; height: 32px;" title="Refuser">❌</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($group_invitations)): ?>
            <div class="film-card" style="padding: 20px;">
                <h3 style="color: #3498db; margin-bottom: 15px;">📂 Invitations aux bocaux</h3>
                <?php foreach ($group_invitations as $inv): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: var(--input-bg); border-radius: 8px; margin-bottom: 10px;">
                        <span><strong><?= htmlspecialchars($inv['inviter_name']) ?></strong> vous invite à rejoindre <strong>"<?= htmlspecialchars($inv['group_name']) ?>"</strong>.</span>
                        <div style="display: flex; gap: 5px;">
                            <a href="invitation_action.php?action=accept&id=<?= $inv['invite_id'] ?>" class="btn-icon btn-check" style="width: 32px; height: 32px;" title="Rejoindre">✅</a>
                            <a href="invitation_action.php?action=reject&id=<?= $inv['invite_id'] ?>" class="btn-icon btn-delete" style="width: 32px; height: 32px;" title="Ignorer">❌</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require "footer.php"; ?>