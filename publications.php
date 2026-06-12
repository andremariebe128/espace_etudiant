<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

$user = current_user($pdo);
$search = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['filtre'] ?? 'all');
$filter = $filter === 'mine' ? 'mine' : 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filter === 'mine') {
    $where[] = 'p.etudiant_id = :user_id';
    $params['user_id'] = (int) $user['id'];
}

if ($search !== '') {
    $where[] = '(p.titre LIKE :search OR p.contenu LIKE :search OR e.nom LIKE :search OR e.prenom LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) AS total
             FROM publications p
             JOIN etudiants e ON e.id = p.etudiant_id
             {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / $perPage));

if ($page > $totalPages) {
    $query = http_build_query([
        'q' => $search,
        'filtre' => $filter,
        'page' => $totalPages,
    ]);
    redirect('publications.php?' . $query);
}

$sql = "SELECT p.*, e.nom, e.prenom, e.photo,
               (SELECT COUNT(*) FROM commentaires c WHERE c.publication_id = p.id) AS total_commentaires
        FROM publications p
        JOIN etudiants e ON e.id = p.etudiant_id
        {$whereSql}
        ORDER BY p.date_creation DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$publications = $stmt->fetchAll();

page_header('Publications');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Publications</p>
        <h1><?= $filter === 'mine' ? 'Mes publications' : 'Toutes les publications' ?></h1>
    </div>
    <a class="primary-button" href="publication_create.php">Nouvelle publication</a>
</section>

<form class="toolbar" action="publications.php" method="get">
    <input name="q" type="search" value="<?= e($search) ?>" placeholder="Rechercher">
    <select name="filtre" aria-label="Filtre">
        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Toutes</option>
        <option value="mine" <?= $filter === 'mine' ? 'selected' : '' ?>>Mes publications</option>
    </select>
    <button class="secondary-button" type="submit">Filtrer</button>
</form>

<?php if (!$publications): ?>
    <article class="empty-state">
        <h2>Aucune publication trouvée</h2>
        <p>Modifiez votre recherche ou créez une nouvelle publication.</p>
    </article>
<?php endif; ?>

<section class="publication-list">
    <?php foreach ($publications as $publication): ?>
        <?php
        $author = [
            'nom' => $publication['nom'],
            'prenom' => $publication['prenom'],
            'photo' => $publication['photo'],
        ];
        $isOwner = (int) $publication['etudiant_id'] === (int) $user['id'];
        ?>
        <article class="publication-card">
            <div class="publication-meta">
                <?= render_avatar($author, 'avatar avatar-small') ?>
                <div>
                    <strong><?= e($publication['prenom']) ?> <?= e($publication['nom']) ?></strong>
                    <span><?= e(format_date($publication['date_creation'])) ?></span>
                </div>
            </div>

            <h2><a href="publication.php?id=<?= (int) $publication['id'] ?>"><?= e($publication['titre']) ?></a></h2>
            <p><?= e(mb_substr($publication['contenu'], 0, 220, 'UTF-8')) ?><?= mb_strlen($publication['contenu'], 'UTF-8') > 220 ? '...' : '' ?></p>

            <div class="card-footer">
                <span><?= (int) $publication['total_commentaires'] ?> commentaire(s)</span>
                <div class="actions-row compact">
                    <a class="secondary-button" href="publication.php?id=<?= (int) $publication['id'] ?>">Lire</a>
                    <?php if ($isOwner): ?>
                        <a class="secondary-button" href="publication_edit.php?id=<?= (int) $publication['id'] ?>">Modifier</a>
                        <a class="danger-link" href="publication_delete.php?id=<?= (int) $publication['id'] ?>">Supprimer</a>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php
            $query = http_build_query([
                'q' => $search,
                'filtre' => $filter,
                'page' => $i,
            ]);
            ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="publications.php?<?= e($query) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>

<?php page_footer(); ?>
