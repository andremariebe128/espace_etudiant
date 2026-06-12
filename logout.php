<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (!is_post()) {
    redirect(is_logged_in() ? 'dashboard.php' : 'index.php');
}

require_csrf('dashboard.php');

destroy_user_session();
redirect('index.php');
