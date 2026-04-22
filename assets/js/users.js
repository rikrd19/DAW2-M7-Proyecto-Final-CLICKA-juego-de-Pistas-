/**
 * Users admin page interactions.
 * Exposes confirmDelete for inline delete buttons.
 */
(function () {
  window.confirmDelete = function (id) {
    var modalEl = document.getElementById('deleteModal');
    var confirmBtn = document.getElementById('confirmDeleteBtn');
    if (!modalEl || !confirmBtn || typeof bootstrap === 'undefined') return;

    var base = modalEl.getAttribute('data-delete-url-base') || '';
    confirmBtn.href = base + encodeURIComponent(String(id));

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  };
})();
