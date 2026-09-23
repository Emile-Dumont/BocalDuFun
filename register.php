<?php
require "db.php";
$error = null;

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Vérif basique
    if ($username && $email && $password) {
        
        // --- NOUVEAUTÉ : ON VÉRIFIE SI LE PSEUDO OU L'EMAIL EXISTE DÉJÀ ---
        $check = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        $existingUser = $check->fetch();

        if ($existingUser) {
            // On affiche un message d'erreur précis selon le problème
            if (strtolower($existingUser['username']) === strtolower($username)) {
                $error = "Ce pseudo est déjà pris, choisis-en un autre ! 🕵️‍♂️";
            } else {
                $error = "Cet email est déjà utilisé pour un autre compte.";
            }
        } else {
            // Si tout est bon, on procède à l'inscription
            try {
                $pdo->beginTransaction();

                // 1. Créer User
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hash]);
                $uid = $pdo->lastInsertId();

                // 2. Créer une liste par défaut "Ma Liste"
                $code = strtoupper(substr(md5(uniqid()), 0, 6));
                $stmt = $pdo->prepare("INSERT INTO groups_table (name, invite_code) VALUES (?, ?)");
                $stmt->execute(["Ma Liste Perso", $code]);
                $gid = $pdo->lastInsertId();

                // 3. Lier User et Liste
                $pdo->prepare("INSERT INTO group_users (user_id, group_id) VALUES (?, ?)")->execute([$uid, $gid]);

                $pdo->commit();

                // Connexion auto
                $_SESSION['user_id'] = $uid;
                $_SESSION['active_group_id'] = $gid;
                
                header("Location: dashboard.php");
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Erreur technique lors de l'inscription.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
require "header.php";
?>

<div class="form-card">
    <h2>Créer un compte</h2>
    <?php if ($error): ?>
        <div class="error" style="background:rgba(231, 76, 60, 0.2); padding:10px; border-radius:8px; margin-bottom:15px; color:#ff6b6b;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="username" placeholder="Pseudo" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button class="btn primary" style="width: 100%; margin-top: 10px;">S'inscrire</button>
    </form>
    <p style="margin-top:20px; font-size:0.9em;">
        Déjà un compte ? <a href="login.php" style="color:var(--primary)">Connexion</a>
    </p>
</div>

<?php require "footer.php"; ?>