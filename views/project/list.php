<!-- removing lines 1-26
<?php include __DIR__ . '/../shared/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Projects</h3>
</div>
<?php if (empty($projects)): ?>
  <div class="alert alert-info">No projects yet.</div>
<?php else: ?>
<table class="table table-striped">
  <thead><tr>
    <th>ID</th><th>Name</th><th>From Ticket</th><th>Created</th><th></th>
  </tr></thead>
  <tbody>
    <?php foreach ($projects as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td><?= htmlspecialchars($p['ticket_title']) ?> (#<?= $p['ticket_id'] ?>)</td>
      <td><?= $p['created_at'] ?></td>
      <td><a class="btn btn-sm btn-outline-primary" href="index.php?page=project_view&id=<?= $p['id'] ?>">View</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php include __DIR__ . '/../shared/footer.php'; ?>
 -->

 // new list
 <?php include __DIR__ . '/../shared/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Projects</h3>
</div>

<form class="row g-2 mb-3" method="get" action="index.php">
  <input type="hidden" name="page" value="projects_list">
  <div class="col-md-6">
    <input type="text" name="q" class="form-control" placeholder="Search project name/description..." value="<?= htmlspecialchars($query ?? '') ?>">
  </div>
  <div class="col-md-2">
    <select name="pp" class="form-select">
      <?php foreach ([10,20,50,100] as $opt): ?>
        <option value="<?= $opt ?>" <?= (string)$opt === (string)($pp ?? 10) ? 'selected':'' ?>><?= $opt ?>/page</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100">Search</button>
  </div>
</form>

<?php if (empty($projects)): ?>
  <div class="alert alert-info">No projects found.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead><tr><th>ID</th><th>Name</th><th>From Ticket</th><th>Created</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($projects as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= htmlspecialchars($p['ticket_title']) ?> (#<?= $p['ticket_id'] ?>)</td>
          <td><?= $p['created_at'] ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="index.php?page=project_view&id=<?= $p['id'] ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php
    $totalPages = max(1, (int)ceil(($total ?? 0) / ($pp ?? 10)));
    $cur = max(1, (int)($pageNum ?? 1));
    $base = 'index.php?page=projects_list&pp='.urlencode((string)$pp).'&q='.urlencode((string)$query).'&p=';
  ?>
  <nav>
    <ul class="pagination">
      <li class="page-item <?= $cur<=1?'disabled':'' ?>"><a class="page-link" href="<?= $base.max(1,$cur-1) ?>">Prev</a></li>
      <?php
        $start = max(1, $cur-2); $end = min($totalPages, $cur+2);
        if ($start > 1) { echo '<li class="page-item"><a class="page-link" href="'.$base.'1">1</a></li>'; if ($start>2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
        for ($i=$start; $i<=$end; $i++) { $active = $i===$cur?'active':''; echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$base.$i.'">'.$i.'</a></li>'; }
        if ($end < $totalPages) { if ($end < $totalPages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; echo '<li class="page-item"><a class="page-link" href="'.$base.$totalPages.'">'.$totalPages.'</a></li>'; }
      ?>
      <li class="page-item <?= $cur>=$totalPages?'disabled':'' ?>"><a class="page-link" href="<?= $base.min($totalPages,$cur+1) ?>">Next</a></li>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/../shared/footer.php'; ?>
