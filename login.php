<?php
require "db.php";
$error = null;

if ($_POST) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        
        // On redirige vers la liste des listes pour choisir où aller
        header("Location: lists.php");
        exit;
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
require "header.php";
?>

<div class="form-card">
    <h2>Connexion</h2>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button class="btn">Connexion</button>
    </form>
    <p style="margin-top:20px; font-size:0.9em;">
        Pas encore de compte ? <a href="register.php" style="color:#f39c12">Créer un compte</a>
    </p>
</div>

<?php require "footer.php"; ?>