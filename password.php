<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);

if (is_post()) {
    require_csrf('password.php');

    $oldPassword = (string) ($_POST['ancien_mot_de_passe'] ?? '');
    $newPassword = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation_mot_de_passe'] ?? '');
    $errors = [];

    if (!isset($_POST['ancien_mot_de_passe']) || empty($oldPassword)) {
        $errors[] = 'L ancien mot de passe est obligatoire.';
    }

    if (!isset($_POST['nouveau_mot_de_passe']) || empty($newPassword)) {
        $errors[] = 'Le nouveau mot de passe est obligatoire.';
    } elseif (!password_is_strong($newPassword)) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caracteres.';
    }

    if (!isset($_POST['confirmation_mot_de_passe']) || empty($confirmation)) {
        $errors[] = 'La confirmation est obligatoire.';
    } elseif ($newPassword !== $confirmation) {
        $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT mot_de_passe FROM etudiants WHERE id = :id');
        $stmt->execute(['id' => (int) $user['id']]);
        $stored = $stmt->fetch();

        if (!$stored || !password_verify($oldPassword, $stored['mot_de_passe'])) {
            $errors[] = 'L ancien mot de passe est incorrect.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE etudiants SET mot_de_passe = :mot_de_passe WHERE id = :id');
        $stmt->execute([
            'mot_de_passe' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => (int) $user['id'],
        ]);

        set_flash('success', 'Mot de passe modifie avec succes.');
        redirect('dashboard.php');
    }

    foreach ($errors as $error) {
        set_flash('error', $error);
    }
}

page_header('Mot de passe');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Securite</p>
        <h1>Modifier mon mot de passe</h1>
    </div>
</section>

<form class="form-card wide-form" action="password.php" method="post" novalidate>
    <?= csrf_input() ?>

    <label for="ancien_mot_de_passe">Ancien mot de passe</label>
    <input id="ancien_mot_de_passe" name="ancien_mot_de_passe" type="password" required>

    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
    <input id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" type="password" minlength="8" required>

    <label for="confirmation_mot_de_passe">Confirmer le nouveau mot de passe</label>
    <input id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" type="password" minlength="8" required>

    <div class="actions-row">
        <button class="primary-button" type="submit">Changer le mot de passe</button>
        <a class="secondary-button" href="dashboard.php">Annuler</a>
    </div>
</form>

<?php page_footer(); ?>
