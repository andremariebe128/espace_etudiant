<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_guest();

$nom = '';
$prenom = '';
$email = '';

if (is_post()) {
    require_csrf('register.php');

    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation_mot_de_passe'] ?? '');
    $errors = [];

    if (!isset($_POST['nom']) || empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    }

    if (!isset($_POST['prenom']) || empty($prenom)) {
        $errors[] = 'Le prenom est obligatoire.';
    }

    if (!isset($_POST['email']) || empty($email)) {
        $errors[] = 'L email est obligatoire.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L email est invalide.';
    }

    if (!isset($_POST['mot_de_passe']) || empty($password)) {
        $errors[] = 'Le mot de passe est obligatoire.';
    } elseif (!password_is_strong($password)) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
    }

    if (!isset($_POST['confirmation_mot_de_passe']) || empty($confirmation)) {
        $errors[] = 'La confirmation du mot de passe est obligatoire.';
    } elseif ($password !== $confirmation) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM etudiants WHERE email = :email');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Cet email est déja utilisé.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO etudiants (nom, prenom, email, mot_de_passe)
             VALUES (:nom, :prenom, :email, :mot_de_passe)'
        );
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'mot_de_passe' => $hash,
        ]);

        set_flash('success', 'Compte cree avec succes. Vous pouvez maintenant vous connecter.');
        redirect('index.php');
    }

    foreach ($errors as $error) {
        set_flash('error', $error);
    }
}

page_header('Inscription');
?>

<section class="auth-layout">
    <div class="hero-panel">
        <p class="eyebrow">Nouveau compte</p>
        <h1>Créer votre espace étudiant</h1>
        </div>

    <form class="form-card" action="register.php" method="post" novalidate>
        <?= csrf_input() ?>

        <div class="two-columns">
            <div>
                <label for="prenom">Prénom</label>
                <input id="prenom" name="prenom" type="text" maxlength="100" value="<?= e($prenom) ?>" required>
            </div>
            <div>
                <label for="nom">Nom</label>
                <input id="nom" name="nom" type="text" maxlength="100" value="<?= e($nom) ?>" required>
            </div>
        </div>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" required>

        <label for="mot_de_passe">Mot de passe</label>
        <input id="mot_de_passe" name="mot_de_passe" type="password" minlength="8" required>

        <label for="confirmation_mot_de_passe">Confirmer le mot de passe</label>
        <input id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" type="password" minlength="8" required>

        <button class="primary-button" type="submit">Créer le compte</button>
        <p class="muted">Deja inscrit ? <a href="index.php">Se connecter</a></p>
    </form>
</section>

<?php page_footer(); ?>
