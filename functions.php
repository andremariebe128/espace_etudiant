<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('espace_etudiant_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => false,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlentities((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function redirect_back(string $fallback = 'dashboard.php'): void
{
    $target = $_SERVER['HTTP_REFERER'] ?? $fallback;
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $targetHost = parse_url($target, PHP_URL_HOST);

    if (str_contains($target, "\n") || str_contains($target, "\r") || ($targetHost && $host && $targetHost !== $host)) {
        $target = $fallback;
    }

    redirect($target);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function valid_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function require_csrf(string $fallback = 'dashboard.php'): void
{
    // Les actions POST sensibles doivent venir d'un formulaire genere par l'application.
    if (!valid_csrf()) {
        set_flash('error', 'Session expiree ou formulaire invalide. Reessayez.');
        redirect($fallback);
    }
}

function is_logged_in(): bool
{
    return isset($_SESSION['etudiant_id']) && is_numeric($_SESSION['etudiant_id']);
}

function destroy_user_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function current_user(PDO $pdo): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, nom, prenom, email, photo, theme, date_creation FROM etudiants WHERE id = :id');
    $stmt->execute(['id' => (int) $_SESSION['etudiant_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        destroy_user_session();
        return null;
    }

    $_SESSION['theme'] = $user['theme'];

    return $user;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Connectez-vous pour acceder a cette page.');
        redirect('index.php');
    }

    global $pdo;

    if (!current_user($pdo)) {
        redirect('index.php');
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

function password_is_strong(string $password): bool
{
    return strlen($password) >= 8;
}

function format_date(?string $date): string
{
    if (!$date) {
        return 'Non disponible';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y H:i', $timestamp) : 'Non disponible';
}

function initials(array $user): string
{
    $prenom = trim((string) ($user['prenom'] ?? ''));
    $nom = trim((string) ($user['nom'] ?? ''));
    $first = $prenom !== '' ? mb_substr($prenom, 0, 1, 'UTF-8') : '';
    $second = $nom !== '' ? mb_substr($nom, 0, 1, 'UTF-8') : '';

    return e(mb_strtoupper($first . $second, 'UTF-8') ?: 'ET');
}

function profile_photo_url(?string $photo): ?string
{
    if (!$photo) {
        return null;
    }

    return 'uploads/profiles/' . rawurlencode($photo);
}

function render_avatar(?array $user, string $class = 'avatar'): string
{
    if ($user && !empty($user['photo'])) {
        return '<img class="' . e($class) . '" src="' . e(profile_photo_url($user['photo'])) . '" alt="Photo de profil">';
    }

    return '<div class="' . e($class) . ' avatar-fallback">' . initials($user ?? []) . '</div>';
}

function delete_profile_photo(?string $photo): void
{
    if (!$photo || !preg_match('/^[a-zA-Z0-9_.-]+$/', $photo)) {
        return;
    }

    $path = __DIR__ . '/uploads/profiles/' . $photo;

    if (is_file($path)) {
        unlink($path);
    }
}

function save_profile_photo(array $file, int $userId, ?string $currentPhoto): array
{
    $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => $currentPhoto, 'error' => null];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => $currentPhoto, 'error' => 'Le fichier n a pas pu etre envoye.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'filename' => $currentPhoto, 'error' => 'La photo ne doit pas depasser 2 Mo.'];
    }

    $tmpPath = $file['tmp_name'] ?? '';

    if (!is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'filename' => $currentPhoto, 'error' => 'Fichier invalide.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'filename' => $currentPhoto, 'error' => 'Formats autorises : JPG, PNG ou WebP.'];
    }

    $directory = __DIR__ . '/uploads/profiles';

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $filename = 'profile_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = $directory . '/' . $filename;

    // Le nom final est genere par le serveur pour eviter les collisions et les chemins malveillants.
    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['ok' => false, 'filename' => $currentPhoto, 'error' => 'Impossible de sauvegarder la photo.'];
    }

    delete_profile_photo($currentPhoto);

    return ['ok' => true, 'filename' => $filename, 'error' => null];
}

function fetch_publication(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.*, e.nom, e.prenom, e.email, e.photo
         FROM publications p
         JOIN etudiants e ON e.id = p.etudiant_id
         WHERE p.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $publication = $stmt->fetch();

    return $publication ?: null;
}

function page_header(string $title): void
{
    global $pdo;

    $user = is_logged_in() ? current_user($pdo) : null;
    $theme = $user['theme'] ?? ($_SESSION['theme'] ?? 'light');
    $theme = $theme === 'dark' ? 'dark' : 'light';
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
    $themeLabel = $theme === 'dark' ? 'Clair' : 'Sombre';
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> - Espace Etudiant</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body class="theme-<?= e($theme) ?>">
        <header class="topbar">
            <a class="brand" href="<?= $user ? 'dashboard.php' : 'index.php' ?>">Espace Etudiant</a>
            <nav class="main-nav" aria-label="Navigation principale">
                <?php if ($user): ?>
                    <a href="dashboard.php">Tableau</a>
                    <a href="publications.php">Publications</a>
                    <a href="publication_create.php">Publier</a>
                    <a href="profile.php">Profil</a>
                    <form action="theme.php" method="post" class="inline-form">
                        <?= csrf_input() ?>
                        <input type="hidden" name="theme" value="<?= e($nextTheme) ?>">
                        <button class="ghost-button" type="submit"><?= e($themeLabel) ?></button>
                    </form>
                    <form action="logout.php" method="post" class="inline-form">
                        <?= csrf_input() ?>
                        <button class="danger-link" type="submit">Deconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="index.php">Connexion</a>
                    <a href="register.php">Inscription</a>
                <?php endif; ?>
            </nav>
        </header>
        <main class="container">
            <?php foreach (consume_flashes() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
    <?php
}

function page_footer(): void
{
    ?>
        </main>
        <footer class="footer">
            <span>Plateforme locale d'espace étudiant.</span>
        </footer>
    </body>
    </html>
    <?php
}
