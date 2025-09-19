<?php
$title = 'Create Ticket';
ob_start();
?>
<h3>Create Ticket</h3>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul>
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="/?action=create" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Subject</label>
    <input name="subject" class="form-control" required value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" rows="6" class="form-control" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Attachments (you may select multiple)</label>
    <input type="file" name="attachments[]" multiple class="form-control">
    <small class="form-text text-muted">Allowed: png,jpg,pdf,docx,txt,zip — max 20MB each</small>
  </div>
  <button class="btn btn-success">Submit Ticket</button>
  <a class="btn btn-link" href="/?action=index">Cancel</a>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
