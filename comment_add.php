<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

if (!is_post()) {
    redirect('publications.php');
}

require_csrf('publications.php');

$user = current_user($pdo);
$publicationId = (int) ($_POST['publication_id'] ?? 0);
$contenu = trim((string) ($_POST['contenu'] ?? ''));

$publication = $publicationId > 0 ? fetch_publication($pdo, $publicationId) : null;

if (!$publication) {
    set_flash('error', 'Publication introuvable.');
    redirect('publications.php');
}

if (!isset($_POST['contenu']) || empty($contenu)) {
    set_flash('error', 'Le commentaire est obligatoire.');
    redirect('publication.php?id=' . $publicationId);
}

$stmt = $pdo->prepare(
    'INSERT INTO commentaires (publication_id, etudiant_id, contenu)
     VALUES (:publication_id, :etudiant_id, :contenu)'
);
$stmt->execute([
    'publication_id' => $publicationId,
    'etudiant_id' => (int) $user['id'],
    'contenu' => $contenu,
]);

set_flash('success', 'Commentaire ajoute.');
redirect('publication.php?id=' . $publicationId);
