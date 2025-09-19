<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Lincoln Tickets</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-light mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php?page=tickets_list">Lincoln Tickets</a>
    <ul class="navbar-nav ms-auto">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <li class="nav-item me-3"><span class="nav-link">Hi, <?= htmlspecialchars($_SESSION['username']) ?></span></li>
        <li class="nav-item"><a class="btn btn-outline-secondary" href="index.php?page=logout">Logout</a></li>
      <?php endif; ?>
      <?php if (!empty($_SESSION['role']) && $_SESSION['role']==='admin'): ?>
  <li class="nav-item"><a class="nav-link" href="index.php?page=admin_users">Admin</a></li>
<?php endif; ?>

    </ul>
  </div>
</nav>
<div class="container">
