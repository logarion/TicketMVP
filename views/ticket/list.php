<?php
// ---- Safe defaults from router/context ----
$pp      = isset($pp) ? (int)$pp : (int)($_GET['pp'] ?? 10);
$query   = isset($query) ? (string)$query : (string)($_GET['q'] ?? '');
$pageNum = isset($pageNum) ? (int)$pageNum : (int)($_GET['p'] ?? 1);
$total   = isset($total) ? (int)$total : 0;
$tickets = $tickets ?? [];

// Filters map (keep in sync with router)
$filters = [
  'department_id' => $_GET['department_id'] ?? ($filters['department_id'] ?? ''),
  'assigned_to'   => $_GET['assigned_to']   ?? ($filters['assigned_to']   ?? ''),
  'group_id'      => $_GET['group_id']      ?? ($filters['group_id']      ?? ''),
  'priority'      => $_GET['priority']      ?? ($filters['priority']      ?? ''),
  'status'        => $_GET['status']        ?? ($filters['status']        ?? ''),
];

// Lookups (if router didn’t preload, try to load—won’t fatal if $pdo not set)
$departments = $departments ?? [];
$users       = $users ?? [];
if (!$departments && isset($pdo)) {
  try { $departments = $pdo->query("SELECT id,name FROM departments ORDER BY name")->fetchAll(); } catch(Throwable $e) {}
}
if (!$users && isset($pdo)) {
  try { $users = $pdo->query("SELECT id,username FROM users ORDER BY username")->fetchAll(); } catch(Throwable $e) {}
}

$h = fn($v) => htmlspecialchars((string)$v ?? '');
?>

<?php include __DIR__ . '/../shared/header.php'; ?>

<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 col-lg-2 mb-3">
      <?php include __DIR__ . '/../shared/sidebar.php'; ?>
    </div>
    <div class="col-md-9 col-lg-10">
      <!-- existing page content starts here -->


<!-- Page Header -->
<div class="page-header">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h1 class="page-title">
        <i class="fas fa-ticket-alt me-3"></i>Tickets
      </h1>
      <p class="page-subtitle">Manage and track support tickets</p>
    </div>
    <a class="btn btn-primary btn-lg" href="index.php?page=ticket_create">
      <i class="fas fa-plus me-2"></i>New Ticket
    </a>
  </div>
</div>

<!-- Filters / Search -->
<div class="filter-section">
  <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter & Search</h5>
  <form class="row g-3" method="get" action="index.php">
  <input type="hidden" name="page" value="tickets_list">

  <div class="col-lg-4 col-md-6">
    <input type="text" name="q" class="form-control"
           placeholder="Search title, description, requester…"
           value="<?= $h($query) ?>">
  </div>

  <div class="col-md-3">
    <select name="department_id" class="form-select">
      <option value="">Department</option>
      <?php foreach ($departments as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= ((string)$filters['department_id']===(string)$d['id'])?'selected':'' ?>>
          <?= $h($d['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <select name="assigned_to" class="form-select">
      <option value="">Assignee</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= ((string)$filters['assigned_to']===(string)$u['id'])?'selected':'' ?>>
          <?= $h($u['username']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Optional: show if you wired these server-side -->
  <div class="col-md-2">
    <select name="priority" class="form-select">
      <option value="">Priority</option>
      <option value="Normal" <?= ($filters['priority']==='Normal')?'selected':''; ?>>Normal</option>
      <option value="Urgent" <?= ($filters['priority']==='Urgent')?'selected':''; ?>>Urgent</option>
    </select>
  </div>

  <div class="col-md-2">
    <select name="status" class="form-select">
      <option value="">Status</option>
      <option <?= ($filters['status']==='New')?'selected':''; ?>>New</option>
      <option <?= ($filters['status']==='In Progress')?'selected':''; ?>>In Progress</option>
      <option <?= ($filters['status']==='Resolved')?'selected':''; ?>>Resolved</option>
      <option <?= ($filters['status']==='Closed')?'selected':''; ?>>Closed</option>
    </select>
  </div>

  <div class="col-md-2">
    <select name="pp" class="form-select">
      <?php foreach ([10,20,50,100] as $opt): ?>
        <option value="<?= $opt ?>" <?= ((string)$opt===(string)$pp)?'selected':''; ?>>
          <?= $opt ?>/page
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100">
      <i class="fas fa-search me-1"></i>Apply
    </button>
  </div>
  </form>
</div>

<?php if (empty($tickets)): ?>
  <div class="card text-center p-5">
    <div class="card-body">
      <i class="fas fa-search fa-3x text-muted mb-3"></i>
      <h5 class="card-title">No tickets found</h5>
      <p class="card-text text-muted">Try adjusting your search criteria or create a new ticket.</p>
      <a href="index.php?page=ticket_create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create New Ticket
      </a>
    </div>
  </div>
<?php else: ?>
  <!-- Tickets Count & Summary -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">
      <i class="fas fa-list me-2"></i>
      Showing <?= count($tickets) ?> of <?= $total ?> tickets
    </h5>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="card-view" onclick="toggleView('card')">
        <i class="fas fa-th-large me-1"></i>Cards
      </button>
      <button class="btn btn-outline-secondary btn-sm" id="table-view" onclick="toggleView('table')">
        <i class="fas fa-table me-1"></i>Table
      </button>
    </div>
  </div>

  <!-- Card View -->
  <div id="tickets-cards" class="row">
    <?php foreach ($tickets as $t): ?>
      <div class="col-lg-6 col-xl-4 mb-4">
        <div class="ticket-card fade-in">
          <div class="ticket-card-header">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="ticket-title">
                  <a href="index.php?page=ticket_view&id=<?= (int)$t['id'] ?>" class="text-decoration-none">
                    <?= $h($t['title']) ?>
                  </a>
                </div>
                <div class="ticket-meta">
                  <span class="me-3">
                    <i class="fas fa-hashtag me-1"></i>#<?= (int)$t['id'] ?>
                  </span>
                  <span class="me-3">
                    <i class="fas fa-calendar me-1"></i>
                    <?php 
                      $created = $t['created_at'] ?? '';
                      if (!empty($created)) {
                        $ts = strtotime($created);
                        echo $ts ? date('M j, Y', $ts) : '—';
                      } else {
                        echo '—';
                      }
                    ?>
                  </span>
                </div>
              </div>
              <div class="d-flex flex-column gap-1 align-items-end">
                <?php $s = $t['status'] ?? 'New'; ?>
                <span class="badge
                  <?= $s==='Closed' ? 'bg-dark' :
                     ($s==='Resolved' ? 'bg-success' :
                     ($s==='In Progress' ? 'bg-warning' : 'bg-secondary')) ?>">
                  <?= $h($s) ?>
                </span>
                <span class="badge <?= ($t['priority']==='Urgent'?'bg-danger':'bg-info') ?>">
                  <?= $h($t['priority'] ?? 'Normal') ?>
                </span>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <div class="row g-2">
              <div class="col-6">
                <small class="text-muted d-block">Created by</small>
                <span class="fw-medium">
                  <i class="fas fa-user me-1"></i><?= $h($t['username'] ?? 'Unknown') ?>
                </span>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">Assigned to</small>
                <span class="fw-medium">
                  <i class="fas fa-user-tag me-1"></i><?= $h($t['assigned_username'] ?? 'Unassigned') ?>
                </span>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">Requester</small>
                <span class="fw-medium">
                  <i class="fas fa-envelope me-1"></i>
                  <?php if (!empty($t['requester_username'])): ?>
                    <?= $h($t['requester_username']) ?>
                  <?php else: ?>
                    <?= $h($t['requester_email'] ?? 'Not set') ?>
                  <?php endif; ?>
                </span>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">Department</small>
                <span class="fw-medium">
                  <i class="fas fa-building me-1"></i><?= $h($t['department_name'] ?? 'No department') ?>
                </span>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-between align-items-center">
            <a href="index.php?page=ticket_view&id=<?= (int)$t['id'] ?>" class="btn btn-primary btn-sm">
              <i class="fas fa-eye me-1"></i>View Details
            </a>
            <div class="dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-ellipsis-h"></i>
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="index.php?page=ticket_edit&id=<?= (int)$t['id'] ?>">
                  <i class="fas fa-edit me-2"></i>Edit
                </a></li>
                <li><a class="dropdown-item" href="index.php?page=project_from_ticket&ticket_id=<?= (int)$t['id'] ?>">
                  <i class="fas fa-project-diagram me-2"></i>Create Project
                </a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Table View (Hidden by default) -->
  <div id="tickets-table" class="d-none">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Assigned To</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td><?= (int)$t['id'] ?></td>
            <td><a href="index.php?page=ticket_view&id=<?= (int)$t['id'] ?>"><?= $h($t['title']) ?></a></td>
            <td><span class="badge <?= ($t['priority']==='Urgent'?'bg-danger':'bg-info') ?>"><?= $h($t['priority'] ?? 'Normal') ?></span></td>
            <td>
              <?php $s = $t['status'] ?? 'New'; ?>
              <span class="badge
                <?= $s==='Closed' ? 'bg-dark' :
                   ($s==='Resolved' ? 'bg-success' :
                   ($s==='In Progress' ? 'bg-warning' : 'bg-secondary')) ?>">
                <?= $h($s) ?>
              </span>
            </td>
            <td><?= $h($t['username'] ?? '') ?></td>
            <td><?= $h($t['assigned_username'] ?? 'Unassigned') ?></td>
            <td>
              <?php 
                $created = $t['created_at'] ?? '';
                if (!empty($created)) {
                  $ts = strtotime($created);
                  echo $ts ? date('M j, Y', $ts) : '—';
                } else {
                  echo '—';
                }
              ?>
            </td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="index.php?page=ticket_view&id=<?= (int)$t['id'] ?>">
                <i class="fas fa-eye me-1"></i>View
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php
    // Build pagination base with all filters preserved
    $totalPages = max(1, (int)ceil($total / max(1, $pp)));
    $cur  = max(1, (int)$pageNum);
    $qs = [
      'page' => 'tickets_list',
      'q'    => (string)$query,
      'pp'   => (string)$pp,
      'p'    => null, // placeholder
      'department_id' => (string)$filters['department_id'],
      'assigned_to'   => (string)$filters['assigned_to'],
      'group_id'      => (string)$filters['group_id'],
      'priority'      => (string)$filters['priority'],
      'status'        => (string)$filters['status'],
    ];
    $build = function($pageNum) use ($qs) {
      $qs['p'] = (string)$pageNum;
      return 'index.php?' . http_build_query(array_filter($qs, fn($v)=>$v!==null));
    };
  ?>
  <nav>
    <ul class="pagination">
      <li class="page-item <?= $cur<=1?'disabled':'' ?>">
        <a class="page-link" href="<?= $build(max(1,$cur-1)) ?>">Prev</a>
      </li>
      <?php
        $start = max(1, $cur-2);
        $end   = min($totalPages, $cur+2);
        if ($start > 1) {
          echo '<li class="page-item"><a class="page-link" href="'.$build(1).'">1</a></li>';
          if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        for ($i=$start; $i<=$end; $i++) {
          $active = $i === $cur ? 'active' : '';
          echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$build($i).'">'.$i.'</a></li>';
        }
        if ($end < $totalPages) {
          if ($end < $totalPages-1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
          echo '<li class="page-item"><a class="page-link" href="'.$build($totalPages).'">'.$totalPages.'</a></li>';
        }
      ?>
      <li class="page-item <?= $cur>=$totalPages?'disabled':'' ?>">
        <a class="page-link" href="<?= $build(min($totalPages,$cur+1)) ?>">Next</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../shared/footer.php'; ?>

