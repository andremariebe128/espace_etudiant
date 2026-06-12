<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);

if (is_post()) {
    require_csrf('delete_account.php');

    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if ($confirmation !== 'SUPPRIMER') {
        set_flash('error', 'Tapez SUPPRIMER pour confirmer la suppression du compte.');
        redirect('delete_account.php');
    }

    // Les publications et commentaires sont supprimes automatiquement par les cles etrangeres.
    delete_profile_photo($user['photo']);

    $stmt = $pdo->prepare('DELETE FROM etudiants WHERE id = :id');
    $stmt->execute(['id' => (int) $user['id']]);

    destroy_user_session();
    redirect('index.php?deleted=1');
}

page_header('Suppression du compte');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Zone sensible</p>
        <h1>Supprimer mon compte</h1>
    </div>
</section>

<form class="form-card wide-form danger-zone" action="delete_account.php" method="post" novalidate>
    <?= csrf_input() ?>

    <p>Cette action supprimera votre compte, vos publications et vos commentaires. Elle est definitive.</p>

    <label for="confirmation">Confirmation</label>
    <input id="confirmation" name="confirmation" type="text" placeholder="SUPPRIMER" required>

    <div class="actions-row">
        <button class="danger-button" type="submit">Supprimer definitivement</button>
        <a class="secondary-button" href="dashboard.php">Annuler</a>
    </div>
</form>

<?php page_footer(); ?>
