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
<aside class="d-none d-md-block">
  <div class="list-group sticky-top" style="top:1rem; min-width: 220px;">
    <a href="index.php?page=tickets_list"
       class="list-group-item list-group-item-action <?= active($page,'tickets_list') ?>">
      🏷️ Tickets
    </a>
    <a href="index.php?page=tickets_list&assigned_to=<?= $h($userId) ?>"
       class="list-group-item list-group-item-action <?= ($page==='tickets_list' && ($_GET['assigned_to'] ?? '') == (string)$userId) ? 'active' : '' ?>">
      📌 My Assigned
    </a>
    <a href="index.php?page=projects_list"
       class="list-group-item list-group-item-action <?= active($page,'projects_list') ?>">
      📁 Projects
    </a>
    <a href="index.php?page=tasks_list"
       class="list-group-item list-group-item-action <?= active($page,'tasks_list') ?>">
      ✅ Tasks
    </a>

    <?php if ($role === 'admin'): ?>
      <div class="mt-3 fw-semibold px-3 text-uppercase small text-muted">Admin</div>
      <a href="index.php?page=admin_users"
         class="list-group-item list-group-item-action <?= active($page,'admin_users') ?>">
        👤 Users
      </a>
      <a href="index.php?page=admin_departments"
         class="list-group-item list-group-item-action <?= active($page,'admin_departments') ?>">
        🏢 Departments
      </a>
      <a href="index.php?page=admin_groups"
         class="list-group-item list-group-item-action <?= active($page,'admin_groups') ?>">
        👥 Groups
      </a>
      <a href="index.php?page=admin_settings"
         class="list-group-item list-group-item-action <?= active($page,'admin_settings') ?>">
        ⚙️ Settings
      </a>
    <?php endif; ?>
  </div>
</aside>
