<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (is_post()) {
    csrf_check();
    if ($user = current_user()) {
        auth_log('logout', $user);
    }
    logout();
}
redirect('login.php');
