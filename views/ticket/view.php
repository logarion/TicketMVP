<?php include __DIR__ . '/../shared/header.php'; ?>
<form method="post" action="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?>" class="d-flex gap-2 mt-2">
  <select name="assigned_to" class="form-select" style="max-width:260px">
    <option value="">Unassigned</option>
    <?php foreach ($users as $u): ?>
      <option value="<?= (int)$u['id'] ?>" <?= ((string)($ticket['assigned_to'] ?? '')===(string)$u['id'])?'selected':'' ?>>
        <?= htmlspecialchars($u['username']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-outline-primary btn-sm" name="assign_ticket" value="1">Update Assignee</button>
</form>

<form method="post" action="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?>" class="mt-2 d-flex gap-2">
  <?php if (($ticket['status'] ?? 'New') !== 'Closed'): ?>
    <button class="btn btn-outline-danger btn-sm" name="close_ticket" value="1"
            onclick="return confirm('Close this ticket?');">
      Close Ticket
    </button>
  <?php else: ?>
    <button class="btn btn-outline-secondary btn-sm" name="reopen_ticket" value="1">
      Reopen
    </button>
  <?php endif; ?>

  <?php if (($ticket['status'] ?? '') !== 'Resolved' && ($ticket['status'] ?? '') !== 'Closed'): ?>
    <button class="btn btn-outline-success btn-sm" name="resolve_ticket" value="1">
      Mark Resolved
    </button>
  <?php endif; ?>
</form>
<?php
$h = fn($v) => htmlspecialchars((string)$v ?? '');
$editMode   = isset($_GET['edit']);
$saved      = !empty($_GET['saved']);
$convSaved  = !empty($_GET['conv_saved']);
?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= $h($error) ?></div>
<?php elseif ($saved): ?>
  <div class="alert alert-success">Ticket saved.</div>
<?php elseif (!empty($conv_error)): ?>
  <div class="alert alert-danger"><?= $h($conv_error) ?></div>
<?php elseif ($convSaved): ?>
  <div class="alert alert-success">Conversation updated & email sent.</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Ticket #<?= $h($ticket['id']) ?></h3>
  <div class="d-flex gap-2">
    <a class="btn btn-secondary" href="index.php?page=tickets_list">Back</a>
    <?php if ($editMode): ?>
      <a class="btn btn-outline-secondary" href="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?>">Cancel</a>
    <?php else: ?>
      <a class="btn btn-outline-primary" href="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?>&edit=1">Edit</a>
    <?php endif; ?>
  </div>
</div>

<form method="post" action="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?><?= $editMode ? '&edit=1':'' ?>">
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-body">
          <!-- Title -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Title</label>
            <?php if ($editMode): ?>
              <input name="title" class="form-control" value="<?= $h($ticket['title']) ?>" required>
            <?php else: ?>
              <div class="fs-5"><?= $h($ticket['title']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Requester -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Requester (email or username)</label>
            <?php
              $reqUsername = $ticket['requester_username'] ?? '';
              $reqEmail    = $ticket['requester_email'] ?? ($ticket['requester_user_email'] ?? '');
              $displayName = $reqUsername !== '' ? $reqUsername : '(no username)';
              $displayEmail= $reqEmail    !== '' ? $reqEmail    : '(no email)';
            ?>
            <?php if ($editMode): ?>
              <input name="requester" class="form-control" value="<?= $h($reqUsername ?: $reqEmail) ?>">
              <div class="form-text">Currently resolved to: <?= $h($displayName) ?> (<?= $h($displayEmail) ?>)</div>
            <?php else: ?>
              <div><?= $h($displayName) ?> <small class="text-muted">(<?= $h($displayEmail) ?>)</small></div>
            <?php endif; ?>
          </div>

          <!-- Meta row -->
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Priority</label>
              <?php if ($editMode): ?>
                <select name="priority" class="form-select">
                  <option value="Normal" <?= ($ticket['priority']==='Normal')?'selected':'' ?>>Normal</option>
                  <option value="Urgent" <?= ($ticket['priority']==='Urgent')?'selected':'' ?>>Urgent</option>
                </select>
              <?php else: ?>
                <span class="badge <?= ($ticket['priority']==='Urgent'?'bg-danger':'bg-secondary') ?>">
                  <?= $h($ticket['priority'] ?? 'Normal') ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Assignee</label>
              <?php if ($editMode): ?>
                <select name="assigned_to" class="form-select">
                  <option value="">Unassigned</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= ((string)($ticket['assigned_to'] ?? '')===(string)$u['id'])?'selected':'' ?>>
                      <?= $h($u['username']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <div><?= $h($ticket['assigned_username'] ?? 'Unassigned') ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Department</label>
              <div><?= $h($ticket['department_name'] ?? '—') ?></div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Status</label>
              <?php if ($editMode): ?>
                <select name="status" class="form-select">
                  <?php foreach (['New','In Progress','Resolved','Closed'] as $st): ?>
                    <option value="<?= $h($st) ?>" <?= (($ticket['status'] ?? 'New')===$st)?'selected':'' ?>><?= $h($st) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <div>
                  <span class="badge
                    <?php
                      $s = $ticket['status'] ?? 'New';
                      echo $s==='Closed' ? 'bg-dark' :
                          ($s==='Resolved' ? 'bg-success' :
                          ($s==='In Progress' ? 'bg-info' : 'bg-secondary'));
                    ?>">
                    <?= $h($ticket['status'] ?? 'New') ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="text-muted mt-2"><small>
            Created by <?= $h($ticket['username'] ?? '') ?> · <?= $h($ticket['created_at'] ?? '') ?>
            <?php if (!empty($ticket['status'])): ?> · Status: <?= $h($ticket['status']) ?><?php endif; ?>
          </small></div>

          <!-- Description -->
          <hr>
          <label class="form-label fw-semibold">Description</label>
          <?php if ($editMode): ?>
            <textarea name="description" class="form-control" rows="6" required><?= $h($ticket['description'] ?? '') ?></textarea>
          <?php else: ?>
            <div style="white-space: pre-wrap;"><?= $h($ticket['description'] ?? '') ?></div>
          <?php endif; ?>

          <?php if ($editMode): ?>
            <div class="mt-3">
              <button class="btn btn-primary" name="save_ticket" value="1">Save Changes</button>
              <a class="btn btn-secondary" href="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?>">Cancel</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

<!-- Conversation (history first, compose below) -->
<div class="card mb-4">
  <div class="card-body">
    <h5 class="mb-3">Conversation</h5>

    <?php
      // Optional success/warning banners (will show if your router sets these query params)
      if (!empty($_GET['conv_saved']) && empty($_GET['emailed'])) {
        echo '<div class="alert alert-success mb-3">Conversation saved (no email sent).</div>';
      } elseif (!empty($_GET['conv_saved']) && !empty($_GET['emailed'])) {
        echo '<div class="alert alert-success mb-3">Conversation saved & email sent.</div>';
      } elseif (!empty($conv_error ?? null)) {
        echo '<div class="alert alert-danger mb-3">'. $h($conv_error) .'</div>';
      }
    ?>

    <!-- HISTORY (read only, newest first) -->
    <?php if (empty($messages)): ?>
      <p class="text-muted">No messages yet.</p>
    <?php else: ?>
      <ul class="list-group mb-4">
        <?php foreach ($messages as $m): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between">
              <strong>
                <?php if (($m['direction'] ?? '') === 'outbound'): ?>
                  You → <?= $h($m['to_email'] ?? '') ?>
                <?php else: ?>
                  <?= $h($m['from_email'] ?? 'Unknown') ?> → You
                <?php endif; ?>
              </strong>
              <small class="text-muted">
                <?= $h($m['created_at'] ?? '') ?>
                <?php if (!empty($m['emailed'])): ?> · emailed<?php endif; ?>
                <?php if (!empty($m['locked'])): ?> · locked<?php endif; ?>
              </small>
            </div>
            <?php if (!empty($m['subject'])): ?>
              <div class="mt-1"><em><?= $h($m['subject']) ?></em></div>
            <?php endif; ?>
            <div class="mt-1" style="white-space: pre-wrap"><?= $h($m['body'] ?? '') ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <!-- COMPOSE (always at bottom) -->
    <?php
      $defaultSubject = 'Update: ' . ($ticket['title'] ?? '');
      $replyTo = $ticket['requester_email'] ?? ($ticket['requester_user_email'] ?? '');
    ?>
    <form method="post" action="index.php?page=ticket_view&id=<?= (int)$ticket['id'] ?>">
      <div class="mb-2">
        <label class="form-label">Subject</label>
        <input name="conversation_subject" class="form-control" value="<?= $h($defaultSubject) ?>">
      </div>
      <div class="mb-2">
        <label class="form-label">Message</label>
        <textarea name="conversation_body" class="form-control" rows="6" required></textarea>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" name="save_conversation" value="1" type="submit">
          Save (no email)
        </button>
        <button class="btn btn-primary" name="email_conversation" value="1" type="submit" <?= $replyTo ? '' : 'disabled' ?>>
          Email Requester
        </button>
      </div>
      <?php if (!$replyTo): ?>
        <div class="form-text text-warning mt-1">
          No requester email on this ticket; set one above to enable emailing.
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>


    <!-- Sidebar (project + attachments) -->
    <div class="col-lg-4">
      <!-- Project -->
      <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <?php if (empty($existingProject)): ?>
              <div class="fw-semibold">No project linked</div>
            <?php else: ?>
              <div class="fw-semibold">Project: <?= $h($existingProject['name']) ?></div>
            <?php endif; ?>
          </div>
          <div>
            <?php if (empty($existingProject)): ?>
              <a class="btn btn-warning btn-sm"
                 onclick="return confirm('Create a project from this ticket?');"
                 href="index.php?page=project_from_ticket&ticket_id=<?= (int)$ticket['id'] ?>">Convert</a>
            <?php else: ?>
              <a class="btn btn-info btn-sm" href="index.php?page=project_view&id=<?= (int)$existingProject['id'] ?>">Open</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Attachments -->
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="mb-3">Attachments</h5>
          <?php if (empty($attachments)): ?>
            <p class="text-muted">No attachments.</p>
          <?php else: ?>
            <ul class="list-group mb-3">
              <?php foreach ($attachments as $a): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span><?= $h($a['original_name']) ?> <small class="text-muted">(<?= (int)($a['size_bytes'] ?? 0) ?> bytes)</small></span>
                  <a class="btn btn-sm btn-outline-secondary"
                     href="index.php?page=attachment_download&id=<?= (int)$a['id'] ?>">Download</a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <form method="post" action="index.php?page=attachment_upload" enctype="multipart/form-data">
            <input type="hidden" name="ticket_id" value="<?= (int)$ticket['id'] ?>">
            <input type="file" name="file" class="form-control" required>
            <button class="btn btn-outline-primary btn-sm mt-2">Upload</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Only shows when editing ticket fields -->
  <?php if ($editMode): ?>
    <div class="mt-2">
      <button class="btn btn-primary" name="save_ticket" value="1">Save Ticket</button>
    </div>
  <?php endif; ?>
</form>

<?php include __DIR__ . '/../shared/footer.php'; ?>
