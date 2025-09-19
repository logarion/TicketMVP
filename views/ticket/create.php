<?php include __DIR__ . '/../shared/header.php'; ?>
<h3>Create Ticket</h3>
<form method="post" action="index.php?page=ticket_create" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input name="title" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="5"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Priority</label>
    <select name="priority" class="form-select">
      <option value="Normal" selected>Normal</option>
      <option value="Urgent">Urgent</option>
    </select>
  </div>

  <!-- NEW: multiple attachments -->
  <div class="mb-3">
    <label class="form-label">Attachments (optional)</label>
    <input type="file" name="attachments[]" class="form-control" multiple>
    <div class="form-text">Allowed: png, jpg, jpeg, gif, pdf, doc, docx, xls, xlsx, csv, txt, zip. Max 20MB each.</div>
  </div>
 <!-- Requester Emails -->
 <div class="mb-3">
  <label class="form-label">Requester (email or username)</label>
  <input name="requester" class="form-control" placeholder="jane@acme.com or janedoe">
  <div class="form-text">Enter an email address or a username in your Users table.</div>
</div>


  <button class="btn btn-success">Submit</button>
  <a class="btn btn-link" href="index.php?page=tickets_list">Cancel</a>
</form>
<?php include __DIR__ . '/../shared/footer.php'; ?>
