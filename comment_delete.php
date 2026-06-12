<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

if (!is_post()) {
    redirect('publications.php');
}

require_csrf('publications.php');

$user = current_user($pdo);
$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT c.*, p.etudiant_id AS proprietaire_publication
     FROM commentaires c
     JOIN publications p ON p.id = c.publication_id
     WHERE c.id = :id'
);
$stmt->execute(['id' => $id]);
$comment = $stmt->fetch();

if (!$comment) {
    set_flash('error', 'Commentaire introuvable.');
    redirect('publications.php');
}

$publicationId = (int) $comment['publication_id'];
$canDelete = (int) $comment['etudiant_id'] === (int) $user['id']
    || (int) $comment['proprietaire_publication'] === (int) $user['id'];

if (!$canDelete) {
    set_flash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
    redirect('publication.php?id=' . $publicationId);
}

$delete = $pdo->prepare('DELETE FROM commentaires WHERE id = :id');
$delete->execute(['id' => $id]);

set_flash('success', 'Commentaire supprime.');
redirect('publication.php?id=' . $publicationId);
