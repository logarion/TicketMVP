<?php
$title = 'Tickets';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Tickets</h3>
  <a class="btn btn-primary" href="/?action=create">Create Ticket</a>
</div>

<form method="get" class="row g-2 mb-3">
  <input type="hidden" name="action" value="index">
  <div class="col-auto">
    <input name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="form-control" placeholder="Search subject or description">
  </div>
  <div class="col-auto">
    <select name="status" class="form-select">
      <option value="">All status</option>
      <option value="open" <?= (($_GET['status'] ?? '')==='open')?'selected':'' ?>>Open</option>
      <option value="in_progress" <?= (($_GET['status'] ?? '')==='in_progress')?'selected':'' ?>>In Progress</option>
      <option value="closed" <?= (($_GET['status'] ?? '')==='closed')?'selected':'' ?>>Closed</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-secondary">Filter</button>
  </div>
</form>

<?php if (empty($tickets)): ?>
  <p>No tickets found.</p>
<?php else: ?>
  <table class="table table-striped">
    <thead><tr>
      <th>ID</th><th>Subject</th><th>Status</th><th>Created</th><th>By</th><th></th>
    </tr></thead>
    <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?= htmlspecialchars($t['subject']) ?></td>
          <td><?= htmlspecialchars($t['status']) ?></td>
          <td><?= htmlspecialchars($t['created_at']) ?></td>
          <td><?= htmlspecialchars($t['creator_email'] ?? '') ?></td>
          <td><a class="btn btn-sm btn-outline-primary" href="/?action=details&id=<?= $t['id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <nav>
    <ul class="pagination">
      <?php for ($p=1;$p<=$pages;$p++): ?>
        <?php
          $qs = $_GET;
          $qs['page'] = $p;
          $qs['action'] = 'index';
          $link = '/?' . http_build_query($qs);
        ?>
        <li class="page-item <?= $p==$page ? 'active' : '' ?>"><a class="page-link" href="<?= $link ?>"><?= $p ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
