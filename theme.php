<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

require_login();

if (!is_post()) {
    redirect('dashboard.php');
}

require_csrf('dashboard.php');

$user = current_user($pdo);
$theme = (string) ($_POST['theme'] ?? 'light');
$theme = $theme === 'dark' ? 'dark' : 'light';

$stmt = $pdo->prepare('UPDATE etudiants SET theme = :theme WHERE id = :id');
$stmt->execute([
    'theme' => $theme,
    'id' => (int) $user['id'],
]);

$_SESSION['theme'] = $theme;
redirect_back('dashboard.php');
