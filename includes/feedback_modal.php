<?php
require_once dirname(__DIR__) . '/config/globals.php';
?>
  <!-- Post-game rating & comment (Bootstrap modal) -->
  <div class="modal fade" id="modal-feedback" tabindex="-1" aria-labelledby="modal-feedback-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-feedback-label">¿Qué tal esta temática?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3" id="modal-feedback-lead"></p>
          <p id="feedback-validation-msg" class="small text-danger mb-2" hidden></p>
          <p id="feedback-login-notice" class="small text-muted mb-0" hidden>
            Para enviar estrellas o comentarios necesitas una cuenta.
            <a href="<?php echo BASE_URL; ?>/pages/register.php">Registrarse</a>
            o
            <a href="<?php echo BASE_URL; ?>/pages/login.php">iniciar sesión</a>.
          </p>
          <div id="feedback-logged-fields">
            <p class="small fw-semibold mb-1">Estrellas</p>
            <div class="star-rating mb-3" id="star-rating" role="group" aria-label="Valoración de 1 a 5">
              <?php for ($s = 1; $s <= 5; $s++): ?>
              <button type="button" class="btn-star" data-value="<?php echo $s; ?>" aria-label="<?php echo $s; ?> de 5">&#9733;</button>
              <?php endfor; ?>
            </div>
            <label for="feedback-comment" class="form-label small mb-1">Comentario (opcional)</label>
            <textarea id="feedback-comment" class="form-control" rows="3" maxlength="2000" placeholder="¿Qué te ha gustado o qué cambiarías?"></textarea>
          </div>
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary" id="btn-feedback-skip" data-bs-dismiss="modal">Omitir</button>
          <button type="button" class="btn btn-primary" id="btn-feedback-send">Enviar</button>
        </div>
      </div>
    </div>
  </div>
