<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Department & Groups for <?= htmlspecialchars($user['username']) ?></h3>
<?php if (!empty($saved)): ?><div class="alert alert-success">Saved.</div><?php endif; ?>

<form method="post" class="card card-body mb-3">
  <h5 class="mb-3">Department</h5>
  <div class="row g-2">
    <div class="col-md-4">
      <select name="department_id" class="form-select">
        <option value="">(none)</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (string)($user['department_id'] ?? '') === (string)$d['id'] ? 'selected':'' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Save Department</button>
  </div>
</form>

<form method="post" class="card card-body">
  <h5 class="mb-3">Groups</h5>
  <div class="row">
    <?php foreach ($groups as $g): ?>
      <div class="col-md-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="groups[]" value="<?= $g['id'] ?>"
                 id="g<?= $g['id'] ?>" <?= in_array($g['id'], $current ?? [], true) ? 'checked':''; ?>>
          <label class="form-check-label" for="g<?= $g['id'] ?>">
            <?= htmlspecialchars($g['name']) ?>
          </label>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary">Save Groups</button>
  </div>
</form>

<a class="btn btn-secondary mt-3" href="index.php?page=admin_users">Back</a>
<?php include __DIR__ . '/../shared/footer.php'; ?>
