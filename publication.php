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

$author = [
    'nom' => $publication['nom'],
    'prenom' => $publication['prenom'],
    'photo' => $publication['photo'],
];
$isOwner = (int) $publication['etudiant_id'] === (int) $user['id'];

$stmt = $pdo->prepare(
    'SELECT c.*, e.nom, e.prenom, e.photo
     FROM commentaires c
     JOIN etudiants e ON e.id = c.etudiant_id
     WHERE c.publication_id = :publication_id
     ORDER BY c.date_creation ASC'
);
$stmt->execute(['publication_id' => $id]);
$comments = $stmt->fetchAll();

page_header($publication['titre']);
?>

<article class="publication-detail">
    <div class="publication-meta">
        <?= render_avatar($author, 'avatar avatar-small') ?>
        <div>
            <strong><?= e($publication['prenom']) ?> <?= e($publication['nom']) ?></strong>
            <span>Publie le <?= e(format_date($publication['date_creation'])) ?></span>
            <?php if (!empty($publication['date_modification'])): ?>
                <span>Modifie le <?= e(format_date($publication['date_modification'])) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <h1><?= e($publication['titre']) ?></h1>
    <p class="publication-content"><?= nl2br(e($publication['contenu'])) ?></p>

    <div class="actions-row">
        <a class="secondary-button" href="publications.php">Retour</a>
        <?php if ($isOwner): ?>
            <a class="secondary-button" href="publication_edit.php?id=<?= $id ?>">Modifier</a>
            <a class="danger-link" href="publication_delete.php?id=<?= $id ?>">Supprimer</a>
        <?php endif; ?>
    </div>
</article>

<section class="comments-section">
    <h2>Commentaires</h2>

    <form class="comment-form" action="comment_add.php" method="post" novalidate>
        <?= csrf_input() ?>
        <input type="hidden" name="publication_id" value="<?= $id ?>">
        <label for="contenu">Ajouter un commentaire</label>
        <textarea id="contenu" name="contenu" rows="4" required></textarea>
        <button class="primary-button" type="submit">Commenter</button>
    </form>

    <?php if (!$comments): ?>
        <p class="muted">Aucun commentaire pour le moment.</p>
    <?php endif; ?>

    <div class="comment-list">
        <?php foreach ($comments as $comment): ?>
            <?php
            $commentAuthor = [
                'nom' => $comment['nom'],
                'prenom' => $comment['prenom'],
                'photo' => $comment['photo'],
            ];
            $canDelete = (int) $comment['etudiant_id'] === (int) $user['id'] || $isOwner;
            ?>
            <article class="comment-card">
                <div class="publication-meta">
                    <?= render_avatar($commentAuthor, 'avatar avatar-small') ?>
                    <div>
                        <strong><?= e($comment['prenom']) ?> <?= e($comment['nom']) ?></strong>
                        <span><?= e(format_date($comment['date_creation'])) ?></span>
                    </div>
                </div>
                <p><?= nl2br(e($comment['contenu'])) ?></p>
                <?php if ($canDelete): ?>
                    <form action="comment_delete.php" method="post" class="inline-form">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                        <button class="danger-link" type="submit">Supprimer</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php page_footer(); ?>
