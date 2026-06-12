<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);
$id = (int) ($_GET['id'] ?? 0);
$publication = $id > 0 ? fetch_publication($pdo, $id) : null;

if (!$publication) {
    set_flash('error', 'Publication introuvable.');
    redirect('publications.php');
}

// Controle de propriete : un etudiant ne modifie que ses propres publications.
if ((int) $publication['etudiant_id'] !== (int) $user['id']) {
    set_flash('error', 'Vous ne pouvez pas modifier cette publication.');
    redirect('publications.php');
}

$titre = (string) $publication['titre'];
$contenu = (string) $publication['contenu'];

if (is_post()) {
    require_csrf('publication_edit.php?id=' . $id);

    $titre = trim((string) ($_POST['titre'] ?? ''));
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    $errors = [];

    if (!isset($_POST['titre']) || empty($titre)) {
        $errors[] = 'Le titre est obligatoire.';
    } elseif (mb_strlen($titre, 'UTF-8') > 150) {
        $errors[] = 'Le titre ne doit pas depasser 150 caracteres.';
    }

    if (!isset($_POST['contenu']) || empty($contenu)) {
        $errors[] = 'Le contenu est obligatoire.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE publications
             SET titre = :titre, contenu = :contenu, date_modification = NOW()
             WHERE id = :id AND etudiant_id = :etudiant_id'
        );
        $stmt->execute([
            'titre' => $titre,
            'contenu' => $contenu,
            'id' => $id,
            'etudiant_id' => (int) $user['id'],
        ]);

        set_flash('success', 'Publication modifiee.');
        redirect('publication.php?id=' . $id);
    }

    foreach ($errors as $error) {
        set_flash('error', $error);
    }
}

page_header('Modifier publication');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Publication</p>
        <h1>Modifier la publication</h1>
    </div>
</section>

<form class="form-card wide-form" action="publication_edit.php?id=<?= $id ?>" method="post" novalidate>
    <?= csrf_input() ?>

    <label for="titre">Titre</label>
    <input id="titre" name="titre" type="text" maxlength="150" value="<?= e($titre) ?>" required>

    <label for="contenu">Contenu</label>
    <textarea id="contenu" name="contenu" rows="9" required><?= e($contenu) ?></textarea>

    <div class="actions-row">
        <button class="primary-button" type="submit">Enregistrer</button>
        <a class="secondary-button" href="publication.php?id=<?= $id ?>">Annuler</a>
    </div>
</form>

<?php page_footer(); ?>
