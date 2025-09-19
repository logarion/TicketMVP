<?php
$title = 'Ticket #' . $ticket['id'];
ob_start();
?>
<h3><?= htmlspecialchars($ticket['subject']) ?></h3>
<p><strong>Status:</strong> <?= htmlspecialchars($ticket['status']) ?> &nbsp; <strong>Created:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>
<p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>

<h5>Attachments</h5>
<?php if (empty($attachments)): ?>
  <p>No attachments.</p>
<?php else: ?>
  <ul>
    <?php foreach ($attachments as $a): ?>
      <li>
        <?= htmlspecialchars($a['original_filename']) ?> (<?= round($a['file_size']/1024,2) ?> KB)
        - <a href="/?action=download&atid=<?= $a['id'] ?>">Download</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<p><a class="btn btn-secondary" href="/?action=index">Back to list</a></p>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
