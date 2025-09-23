</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle between card and table view
function toggleView(view) {
  const cardsView = document.getElementById('tickets-cards');
  const tableView = document.getElementById('tickets-table');
  const cardBtn = document.getElementById('card-view');
  const tableBtn = document.getElementById('table-view');
  
  // Defensive guards - ensure all elements exist
  if (!cardsView || !tableView || !cardBtn || !tableBtn) {
    return;
  }
  
  if (view === 'card') {
    cardsView.classList.remove('d-none');
    tableView.classList.add('d-none');
    cardBtn.classList.add('active');
    tableBtn.classList.remove('active');
    localStorage.setItem('ticketView', 'card');
  } else {
    cardsView.classList.add('d-none');
    tableView.classList.remove('d-none');
    cardBtn.classList.remove('active');
    tableBtn.classList.add('active');
    localStorage.setItem('ticketView', 'table');
  }
}

// Initialize view based on saved preference (only on ticket list page)
document.addEventListener('DOMContentLoaded', function() {
  const cardsView = document.getElementById('tickets-cards');
  const tableView = document.getElementById('tickets-table');
  
  // Only run if ticket elements exist (on ticket list page)
  if (cardsView && tableView) {
    const savedView = localStorage.getItem('ticketView') || 'card';
    toggleView(savedView);
  }
});
</script>
</body>
</html>
