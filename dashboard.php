<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM publications WHERE etudiant_id = :id');
$stmt->execute(['id' => (int) $user['id']]);
$myPublications = (int) $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM commentaires WHERE etudiant_id = :id');
$stmt->execute(['id' => (int) $user['id']]);
$myComments = (int) $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM publications');
$stmt->execute();
$allPublications = (int) $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT MAX(date_creation) AS derniere_publication FROM publications WHERE etudiant_id = :id');
$stmt->execute(['id' => (int) $user['id']]);
$lastPublication = $stmt->fetch()['derniere_publication'] ?? null;

page_header('Tableau de bord');
?>

<section class="dashboard-hero">
    <div>
        <p class="eyebrow">Bienvenue</p>
        <h1><?= e($user['prenom']) ?> <?= e($user['nom']) ?></h1>
        <p><?= e($user['email']) ?></p>
    </div>
    <?= render_avatar($user, 'avatar avatar-large') ?>
</section>

<section class="stats-grid" aria-label="Statistiques">
    <article class="stat-card">
        <span>Mes publications</span>
        <strong><?= $myPublications ?></strong>
    </article>
    <article class="stat-card">
        <span>Mes commentaires</span>
        <strong><?= $myComments ?></strong>
    </article>
    <article class="stat-card">
        <span>Publications totales</span>
        <strong><?= $allPublications ?></strong>
    </article>
    <article class="stat-card">
        <span>Derniere publication</span>
        <strong class="small-stat"><?= e(format_date($lastPublication)) ?></strong>
    </article>
</section>

<section class="content-grid">
    <article class="panel">
        <h2>Informations personnelles</h2>
        <dl class="info-list">
            <div><dt>Nom</dt><dd><?= e($user['nom']) ?></dd></div>
            <div><dt>Prenom</dt><dd><?= e($user['prenom']) ?></dd></div>
            <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
            <div><dt>Compte cree le</dt><dd><?= e(format_date($user['date_creation'])) ?></dd></div>
        </dl>
        <div class="actions-row">
            <a class="secondary-button" href="profile.php">Modifier le profil</a>
            <a class="secondary-button" href="password.php">Mot de passe</a>
        </div>
    </article>

    <article class="panel">
        <h2>Navigation</h2>
        <div class="link-list">
            <a href="publication_create.php">Creer une publication</a>
            <a href="publications.php?filtre=mine">Voir mes publications</a>
            <a href="publications.php">Voir toutes les publications</a>
            <a class="danger-text" href="delete_account.php">Supprimer mon compte</a>
        </div>
    </article>
</section>

<?php page_footer(); ?>
