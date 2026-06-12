<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);
$titre = '';
$contenu = '';

if (is_post()) {
    require_csrf('publication_create.php');

    $titre = trim((string) ($_POST['titre'] ?? ''));
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    $errors = [];

    if (!isset($_POST['titre']) || empty($titre)) {
        $errors[] = 'Le titre est obligatoire.';
    } elseif (mb_strlen($titre, 'UTF-8') > 150) {
        $errors[] = 'Le titre ne doit pas depasser 150 caractères.';
    }

    if (!isset($_POST['contenu']) || empty($contenu)) {
        $errors[] = 'Le contenu est obligatoire.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO publications (etudiant_id, titre, contenu)
             VALUES (:etudiant_id, :titre, :contenu)'
        );
        $stmt->execute([
            'etudiant_id' => (int) $user['id'],
            'titre' => $titre,
            'contenu' => $contenu,
        ]);

        set_flash('success', 'Publication créée.');
        redirect('publications.php?filtre=mine');
    }

    foreach ($errors as $error) {
        set_flash('error', $error);
    }
}

page_header('Nouvelle publication');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Publication</p>
        <h1>Nouvelle publication</h1>
    </div>
</section>

<form class="form-card wide-form" action="publication_create.php" method="post" novalidate>
    <?= csrf_input() ?>

    <label for="titre">Titre</label>
    <input id="titre" name="titre" type="text" maxlength="150" value="<?= e($titre) ?>" required>

    <label for="contenu">Contenu</label>
    <textarea id="contenu" name="contenu" rows="9" required><?= e($contenu) ?></textarea>

    <div class="actions-row">
        <button class="primary-button" type="submit">Publier</button>
        <a class="secondary-button" href="publications.php">Annuler</a>
    </div>
</form>

<?php page_footer(); ?>
