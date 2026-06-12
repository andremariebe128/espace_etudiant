<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$publication = $id > 0 ? fetch_publication($pdo, $id) : null;

if (!$publication) {
    set_flash('error', 'Publication introuvable.');
    redirect('publications.php');
}

if ((int) $publication['etudiant_id'] !== (int) $user['id']) {
    set_flash('error', 'Vous ne pouvez pas supprimer cette publication.');
    redirect('publications.php');
}

if (is_post()) {
    require_csrf('publication_delete.php?id=' . $id);

    $stmt = $pdo->prepare('DELETE FROM publications WHERE id = :id AND etudiant_id = :etudiant_id');
    $stmt->execute([
        'id' => $id,
        'etudiant_id' => (int) $user['id'],
    ]);

    set_flash('success', 'Publication supprimee.');
    redirect('publications.php?filtre=mine');
}

page_header('Supprimer publication');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Confirmation</p>
        <h1>Supprimer cette publication</h1>
    </div>
</section>

<form class="form-card wide-form danger-zone" action="publication_delete.php" method="post">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <h2><?= e($publication['titre']) ?></h2>
    <p>La publication et ses commentaires seront supprimes.</p>
    <div class="actions-row">
        <button class="danger-button" type="submit">Supprimer</button>
        <a class="secondary-button" href="publication.php?id=<?= $id ?>">Annuler</a>
    </div>
</form>

<?php page_footer(); ?>
