<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

require_login();
redirect('members.php');
