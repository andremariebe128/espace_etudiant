<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_guest();

$email = '';

if (isset($_GET['deleted'])) {
    set_flash('success', 'Votre compte a été supprimé. Vous pouvez creer un nouveau compte si necessaire.');
}

if (is_post()) {
    require_csrf('index.php');

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['mot_de_passe'] ?? '');
    $errors = [];

    // Validation explicite des champs obligatoires demandes dans le sujet.
    if (!isset($_POST['email']) || empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email est invalide." ;
    }

    if (!isset($_POST['mot_de_passe']) || empty($password)) {
        $errors[] = 'Le mot de passe est obligatoire.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, mot_de_passe, theme FROM etudiants WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['etudiant_id'] = (int) $user['id'];
            $_SESSION['theme'] = $user['theme'];
            set_flash('success', 'Connexion reussie. Bon retour, ' . $user['prenom'] . ' !');
            redirect('dashboard.php');
        }

        $errors[] = 'Email ou mot de passe incorrect.';
    }

    foreach ($errors as $error) {
        set_flash('error', $error);
    }
}

page_header('Connexion');
?>

<section class="auth-layout">
    <div class="hero-panel">
        <p class="eyebrow">Plateforme securisee</p>
        <h1>Connexion à l'espace étudiant</h1>
        <p>Accedez à votre tableau de bord, vos publications, vos commentaires et vos informations de profil.</p>
    </div>

    <form class="form-card" action="index.php" method="post" novalidate>
        <?= csrf_input() ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" required>

        <label for="mot_de_passe">Mot de passe</label>
        <input id="mot_de_passe" name="mot_de_passe" type="password" required>

        <button class="primary-button" type="submit">Se connecter</button>
        <p class="muted">Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
    </form>
</section>

<?php page_footer(); ?>
