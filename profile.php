<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);
$nom = (string) $user['nom'];
$prenom = (string) $user['prenom'];
$email = (string) $user['email'];

if (is_post()) {
    require_csrf('profile.php');

    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
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

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM etudiants WHERE email = :email AND id <> :id');
        $stmt->execute([
            'email' => $email,
            'id' => (int) $user['id'],
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Cet email est deja utilise par un autre compte.';
        }
    }

    $photoResult = ['ok' => true, 'filename' => $user['photo'], 'error' => null];

    if (!$errors && isset($_FILES['photo'])) {
        $photoResult = save_profile_photo($_FILES['photo'], (int) $user['id'], $user['photo']);

        if (!$photoResult['ok']) {
            $errors[] = (string) $photoResult['error'];
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE etudiants
             SET nom = :nom, prenom = :prenom, email = :email, photo = :photo
             WHERE id = :id'
        );
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'photo' => $photoResult['filename'],
            'id' => (int) $user['id'],
        ]);

        set_flash('success', 'Profil mis a jour.');
        redirect('profile.php');
    }

    foreach ($errors as $error) {
        set_flash('error', $error);
    }
}

page_header('Profil');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Compte</p>
        <h1>Modifier mon profil</h1>
    </div>
    <?= render_avatar($user, 'avatar avatar-large') ?>
</section>

<form class="form-card wide-form" action="profile.php" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_input() ?>

    <div class="two-columns">
        <div>
            <label for="prenom">Prenom</label>
            <input id="prenom" name="prenom" type="text" maxlength="100" value="<?= e($prenom) ?>" required>
        </div>
        <div>
            <label for="nom">Nom</label>
            <input id="nom" name="nom" type="text" maxlength="100" value="<?= e($nom) ?>" required>
        </div>
    </div>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" required>

    <label for="photo">Photo de profil</label>
    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
    <p class="muted">Formats acceptes : JPG, PNG ou WebP. Taille maximale : 2 Mo.</p>

    <div class="actions-row">
        <button class="primary-button" type="submit">Enregistrer</button>
        <a class="secondary-button" href="dashboard.php">Retour</a>
    </div>
</form>

<?php page_footer(); ?>
