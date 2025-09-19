
<?php
$pp      = isset($pp) ? (int)$pp : (int)($_GET['pp'] ?? 10);
$query   = isset($query) ? (string)$query : (string)($_GET['q'] ?? '');
$pageNum = isset($pageNum) ? (int)$pageNum : (int)($_GET['p'] ?? 1);
$total   = isset($total) ? (int)$total : 0;
$tickets = $tickets ?? [];
$filters = [
  'department_id' => $_GET['department_id'] ?? '',
  'group_id'      => $_GET['group_id'] ?? '',
  'assigned_to'   => $_GET['assigned_to'] ?? '',
  'priority'      => $_GET['priority'] ?? '',
  'status'        => $_GET['status'] ?? '',
];
?>

<?php include __DIR__ . '/../shared/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Tickets</h3>
  <a class="btn btn-primary" href="index.php?page=ticket_create">New Ticket</a>
</div>

<form class="row g-2 mb-3" method="get" action="index.php">
  <input type="hidden" name="page" value="tickets_list">

  <div class="col-lg-4 col-md-6">
    <input type="text" name="q" class="form-control" placeholder="Search title/description/email..."
           value="<?= htmlspecialchars($query) ?>">
  </div>

  <div class="col-md-2">
    <select name="department_id" class="form-select">
      <option value="">Department</option>
      <?php foreach (($departments ?? []) as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ((string)$filters['department_id']===(string)$d['id'])?'selected':'' ?>>
          <?= htmlspecialchars($d['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="group_id" class="form-select">
      <option value="">Group</option>
      <?php foreach (($groups ?? []) as $g): ?>
        <option value="<?= $g['id'] ?>" <?= ((string)$filters['group_id']===(string)$g['id'])?'selected':'' ?>>
          <?= htmlspecialchars($g['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <select name="assigned_to" class="form-select">
      <option value="">Assignee</option>
      <?php foreach (($users ?? []) as $u): ?>
        <option value="<?= $u['id'] ?>" <?= ((string)$filters['assigned_to']===(string)$u['id'])?'selected':'' ?>>
          <?= htmlspecialchars($u['username']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-1">
    <select name="priority" class="form-select">
      <option value="">Priority</option>
      <option value="Normal" <?= $filters['priority']==='Normal'?'selected':'' ?>>Normal</option>
      <option value="Urgent" <?= $filters['priority']==='Urgent'?'selected':'' ?>>Urgent</option>
    </select>
  </div>

  <div class="col-md-1">
    <select name="status" class="form-select">
      <option value="">Status</option>
      <option <?= $filters['status']==='New'?'selected':'' ?>>New</option>
      <option <?= $filters['status']==='In Progress'?'selected':'' ?>>In Progress</option>
      <option <?= $filters['status']==='Resolved'?'selected':'' ?>>Resolved</option>
      <option <?= $filters['status']==='Closed'?'selected':'' ?>>Closed</option>
    </select>
  </div>

  <div class="col-md-2">
    <select name="pp" class="form-select">
      <?php foreach ([10,20,50,100] as $opt): ?>
        <option value="<?= $opt ?>" <?= (string)$opt === (string)$pp ? 'selected':'' ?>><?= $opt ?>/page</option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100">Apply</button>
  </div>
</form>


<?php if (empty($tickets)): ?>
  <div class="alert alert-info">No tickets found.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Created By</th>
          <th>Assigned To</th>   <!-- NEW -->
          <th>Department</th>    <!-- NEW -->
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tickets as $t): ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><?= htmlspecialchars($t['title']) ?></td>
          <td><?= htmlspecialchars($t['priority']) ?></td>
          <td><?= htmlspecialchars($t['status'] ?? '') ?></td>
          <td><?= htmlspecialchars($t['username'] ?? '') ?></td>
          <td><?= htmlspecialchars($t['assigned_username'] ?? 'Unassigned') ?></td> <!-- NEW -->
          <td><?= htmlspecialchars($t['department_name'] ?? '') ?></td>              <!-- NEW -->
          <td><?= htmlspecialchars($t['created_at']) ?></td>
          <td>
            <a href="index.php?page=ticket_view&id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">View</a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
              <a href="index.php?page=ticket_edit&id=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php
  $totalPages = max(1, (int)ceil($total / max(1, $pp)));
  $cur  = max(1, (int)$pageNum);

  // Build base URL with all filters
  $qs = [
    'page' => 'tickets_list',
    'q'    => (string)$query,
    'pp'   => (string)$pp,
    'department_id' => (string)$filters['department_id'],
    'group_id'      => (string)$filters['group_id'],
    'assigned_to'   => (string)$filters['assigned_to'],
    'priority'      => (string)$filters['priority'],
    'status'        => (string)$filters['status'],
  ];
  $base = 'index.php?'.http_build_query($qs).'&p=';
  ?>
  <nav>
    <ul class="pagination">
      <li class="page-item <?= $cur<=1?'disabled':'' ?>">
        <a class="page-link" href="<?= $base . max(1,$cur-1) ?>">Prev</a>
      </li>
      <?php
        // compact page window
        $start = max(1, $cur-2);
        $end   = min($totalPages, $cur+2);
        if ($start > 1) {
          echo '<li class="page-item"><a class="page-link" href="'.$base.'1">1</a></li>';
          if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        for ($i=$start; $i<=$end; $i++) {
          $active = $i === $cur ? 'active' : '';
          echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$base.$i.'">'.$i.'</a></li>';
        }
        if ($end < $totalPages) {
          if ($end < $totalPages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          echo '<li class="page-item"><a class="page-link" href="'.$base.$totalPages.'">'.$totalPages.'</a></li>';
        }
      ?>
      <li class="page-item <?= $cur>=$totalPages?'disabled':'' ?>">
        <a class="page-link" href="<?= $base . min($totalPages,$cur+1) ?>">Next</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/../shared/footer.php'; ?>
