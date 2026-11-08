<?php
/** @var string $title @var string $content */
$page = basename($_SERVER['SCRIPT_NAME'], '.php');
$isActive = fn(array $pages): string => in_array($page, $pages, true) ? ' active' : '';
?>
<!doctype html>
<html lang="en-NZ">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> · <?= e(config()['app_name']) ?></title>
  <link rel="stylesheet" href="assets/style.css">
  <script src="assets/app.js" defer></script>
</head>
<body>
  <header class="site-header">
    <a class="site-name" href="./"><?= e(config()['app_name']) ?></a>
    <?php if (isset($user)): ?>
      <nav class="menu">
        <a class="menu-top<?= $isActive(['members', 'member-edit', 'subscription-edit', 'access-item-edit']) ?>" href="members.php">Members</a>
        <details class="dropdown<?= $isActive(['report-members', 'report-barrier-cards', 'report-pool', 'report-tennis']) ?>">
          <summary class="menu-top">Reports</summary>
          <div class="dropdown-menu">
            <a href="report-members.php">Members</a>
            <a href="report-barrier-cards.php">Barrier cards</a>
            <a href="report-pool.php">Pool access</a>
            <a href="report-tennis.php">Tennis access</a>
          </div>
        </details>
        <details class="dropdown<?= $isActive(['export-cards', 'export-menumate', 'export-brevo']) ?>">
          <summary class="menu-top">Exports</summary>
          <div class="dropdown-menu">
            <a href="export-cards.php">Print cards</a>
            <a href="export-menumate.php">Update Menumate</a>
            <a href="export-brevo.php">Update Brevo mailing list</a>
          </div>
        </details>
        <details class="dropdown<?= $isActive(['users', 'user-edit', 'groups', 'companies', 'interests', 'brevo-lists', 'membership-types', 'life-renewals', 'login-activity']) ?>">
          <summary class="menu-top">Management</summary>
          <div class="dropdown-menu">
            <a href="users.php">Users</a>
            <a href="groups.php">Groups</a>
            <a href="companies.php">Companies</a>
            <a href="interests.php">Interests</a>
            <a href="brevo-lists.php">Brevo lists</a>
            <a href="membership-types.php">Membership types</a>
            <a href="life-renewals.php">Life member renewals</a>
            <a href="login-activity.php">Login activity</a>
          </div>
        </details>
        <details class="dropdown align-right<?= $isActive(['profile', 'profile-totp']) ?>">
          <summary class="menu-top">Profile</summary>
          <div class="dropdown-menu">
            <span class="menu-note">Logged in as <?= e($user['display_name']) ?></span>
            <a href="profile.php">My profile</a>
            <form method="post" action="logout.php">
              <?= csrf_field() ?>
              <button type="submit">Log out</button>
            </form>
          </div>
        </details>
      </nav>
    <?php endif; ?>
  </header>
  <main>
    <?= $content ?>
  </main>
</body>
</html>
