/**
 * Reusable password visibility toggle.
 * Attach to any button with data-password-toggle="#inputId".
 */
(function () {
  function togglePassword(btn, input) {
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';

    var icon = btn.querySelector('i');
    if (icon) {
      icon.className = isHidden ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
    }

    btn.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var buttons = document.querySelectorAll('[data-password-toggle]');
    buttons.forEach(function (btn) {
      var selector = btn.getAttribute('data-password-toggle');
      if (!selector) return;
      var input = document.querySelector(selector);
      if (!input) return;

      btn.addEventListener('click', function () {
        togglePassword(btn, input);
      });
    });
  });
})();
