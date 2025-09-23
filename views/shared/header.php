<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Lincoln Tickets</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php?page=tickets_list">
      <i class="fas fa-ticket-alt me-2"></i>Lincoln Tickets
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item me-3">
            <span class="nav-link">
              <i class="fas fa-user me-1"></i>Hi, <?= htmlspecialchars($_SESSION['username']) ?>
            </span>
          </li>
          <?php if (!empty($_SESSION['role']) && $_SESSION['role']==='admin'): ?>
            <li class="nav-item me-2">
              <a class="nav-link" href="index.php?page=admin_users">
                <i class="fas fa-cog me-1"></i>Admin
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="btn btn-outline-secondary" href="index.php?page=logout">
              <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
