<form method="post" action="index.php?page=ticket_assign_quick" class="d-flex gap-2 align-items-end mb-3">
  <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
  <div>
    <label class="form-label">Assignee</label>
    <select name="assigned_to" class="form-select">
      <option value="">Unassigned</option>
      <?php
        // ensure $users exists; if not, load here (fallback)
        if (!isset($users)) {
          $users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();
        }
        foreach ($users as $u):
      ?>
        <option value="<?= $u['id'] ?>" <?= (string)($ticket['assigned_to'] ?? '') === (string)$u['id'] ? 'selected':'' ?>>
          <?= htmlspecialchars($u['username']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-outline-primary">Update</button>
  <a class="btn btn-outline-secondary" href="index.php?page=ticket_edit&id=<?= $ticket['id'] ?>">Edit Ticket</a>
</form>
<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Ticket #<?= $ticket['id'] ?></h3>
<p><strong>Title:</strong> <?= htmlspecialchars($ticket['title']) ?></p>
<p><strong>Priority:</strong> <span class="badge <?= $ticket['priority']==='Urgent'?'bg-danger':'bg-secondary' ?>"><?= $ticket['priority'] ?></span></p>
<p><strong>Status:</strong> <?= htmlspecialchars($ticket['status']) ?></p>
<p><strong>Created by:</strong> <?= htmlspecialchars($ticket['requester_username']) ?></p>
<p><strong>Requester:</strong>
  <?php if (!empty($ticket['requester_username'])): ?>
    <?= htmlspecialchars($ticket['requester_username']) ?>
    <small class="text-muted">
      (<?= htmlspecialchars($ticket['requester_email'] ?? $ticket['requester_user_email'] ?? '') ?>)
    </small>
  <?php else: ?>
    <?= htmlspecialchars($ticket['requester_email'] ?? '—') ?>
  <?php endif; ?>
</p>
<hr>
<p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>

<hr>
<h5>Attachments</h5>
<?php if (empty($attachments)): ?>
  <p class="text-muted">No attachments.</p>
<?php else: ?>
  <ul class="list-group">
    <?php foreach ($attachments as $a): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <strong><?= htmlspecialchars($a['original_name']) ?></strong>
          <small class="text-muted">(<?= number_format($a['size_bytes']/1024, 1) ?> KB, <?= htmlspecialchars($a['mime_type'] ?? 'n/a') ?>)</small>
        </div>
        <a class="btn btn-sm btn-outline-primary"
           href="index.php?page=attachment_download&id=<?= $a['id'] ?>">Download</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<hr>
<h5>Conversation</h5>
<?php if (empty($messages)): ?>
  <p class="text-muted">No messages yet.</p>
<?php else: ?>
  <ul class="list-group mb-3">
    <?php foreach ($messages as $m): ?>
      <li class="list-group-item">
        <div class="d-flex justify-content-between">
          <strong><?= $m['direction']==='outbound' ? 'You → ' . htmlspecialchars($m['to_email']) : htmlspecialchars($m['from_email']) . ' → You' ?></strong>
          <small class="text-muted"><?= htmlspecialchars($m['created_at']) ?></small>
        </div>
        <?php if (!empty($m['subject'])): ?>
          <div><em><?= htmlspecialchars($m['subject']) ?></em></div>
        <?php endif; ?>
        <div style="white-space:pre-wrap"><?= htmlspecialchars($m['body']) ?></div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<!-- Reply form -->
<div class="card card-body">
  <h6 class="mb-3">Send a reply</h6>
  <form method="post" action="index.php?page=ticket_send_email">
    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
    <div class="mb-2">
      <label class="form-label">To</label>
      <input name="to" class="form-control" value="<?= htmlspecialchars($ticket['requester_email'] ?? '') ?>" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Subject</label>
      <input name="subject" class="form-control" value="Re: <?= htmlspecialchars($ticket['title']) ?>" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Body</label>
      <textarea name="body" class="form-control" rows="5" required></textarea>
    </div>
    <button class="btn btn-primary btn-sm">Send</button>
  </form>
</div>



<?php if (empty($existingProject)): ?>
  <a class="btn btn-sm btn-warning mt-2" 
     href="index.php?page=project_from_ticket&ticket_id=<?= $ticket['id'] ?>"
     onclick="return confirm('Create a project from this ticket?');">
     Convert to Project
  </a>
<?php else: ?>
  <a class="btn btn-sm btn-info mt-2" 
     href="index.php?page=project_view&id=<?= $existingProject['id'] ?>">
     View Project
  </a>
<?php endif; ?>

<a class="btn btn-secondary mt-3" href="index.php?page=tickets_list">Back</a>
<?php include __DIR__ . '/../shared/footer.php'; ?>
