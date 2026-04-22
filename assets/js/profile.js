/**
 * Profile avatar picker interactions.
 * Exposes a global handler used by avatar tiles.
 */
(function () {
  window.pickAvatarFromTile = function (tile) {
    if (!tile) return;

    var img = tile.querySelector('img');
    if (!img) return;

    var preview = document.querySelector('.avatar-preview');
    var hiddenInput = document.getElementById('selected_avatar');
    if (preview) preview.src = img.currentSrc || img.src;
    if (hiddenInput) hiddenInput.value = img.currentSrc || img.src;

    document.querySelectorAll('.avatar-option img').forEach(function (item) {
      item.classList.remove('border-primary', 'border-3');
    });
    img.classList.add('border-primary', 'border-3');
  };
})();
