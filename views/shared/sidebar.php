<?php
// views/shared/sidebar.php
$h = fn($v) => htmlspecialchars((string)$v ?? '');
$role = $_SESSION['role'] ?? 'user';
$userId = (int)($_SESSION['user_id'] ?? 0);
$page  = $_GET['page'] ?? 'tickets_list';

function active($current, $needle) {
  return $current === $needle ? 'active' : '';
}
?>
<aside class="sidebar d-none d-md-block">
  <nav class="nav flex-column">
    <a href="index.php?page=tickets_list"
       class="nav-link <?= active($page,'tickets_list') ?>">
      <i class="fas fa-ticket-alt"></i>
      All Tickets
    </a>
    <a href="index.php?page=tickets_list&assigned_to=<?= $h($userId) ?>"
       class="nav-link <?= ($page==='tickets_list' && ($_GET['assigned_to'] ?? '') == (string)$userId) ? 'active' : '' ?>">
      <i class="fas fa-user-tag"></i>
      My Assigned
    </a>
    <a href="index.php?page=ticket_create"
       class="nav-link <?= active($page,'ticket_create') ?>">
      <i class="fas fa-plus-circle"></i>
      New Ticket
    </a>
    <a href="index.php?page=projects_list"
       class="nav-link <?= active($page,'projects_list') ?>">
      <i class="fas fa-project-diagram"></i>
      Projects
    </a>
    <a href="index.php?page=tasks_list"
       class="nav-link <?= active($page,'tasks_list') ?>">
      <i class="fas fa-tasks"></i>
      Tasks
    </a>

    <?php if ($role === 'admin'): ?>
      <hr class="my-3">
      <div class="nav-section-title">
        <i class="fas fa-shield-alt me-2"></i>Administration
      </div>
      <a href="index.php?page=admin_users"
         class="nav-link <?= active($page,'admin_users') ?>">
        <i class="fas fa-users"></i>
        Users
      </a>
      <a href="index.php?page=admin_departments"
         class="nav-link <?= active($page,'admin_departments') ?>">
        <i class="fas fa-building"></i>
        Departments
      </a>
      <a href="index.php?page=admin_groups"
         class="nav-link <?= active($page,'admin_groups') ?>">
        <i class="fas fa-users-cog"></i>
        Groups
      </a>
      <a href="index.php?page=admin_settings"
         class="nav-link <?= active($page,'admin_settings') ?>">
        <i class="fas fa-cog"></i>
        Settings
      </a>
    <?php endif; ?>
  </nav>
</aside>
