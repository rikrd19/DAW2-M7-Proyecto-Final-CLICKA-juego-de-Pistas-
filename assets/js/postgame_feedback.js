'use strict';

/**
 * Shared post-game modal: stars + optional comment → api/feedback.php
 * Requires globals from the page: CAN_SEND_FEEDBACK (boolean)
 * Call: showPostGameFeedbackModal(labelForUi, temaForApi)
 */
(function () {
    const canSend =
        typeof CAN_SEND_FEEDBACK !== 'undefined' && CAN_SEND_FEEDBACK === true;

    const modalEl = document.getElementById('modal-feedback');
    const leadEl = document.getElementById('modal-feedback-lead');
    const loginNoticeEl = document.getElementById('feedback-login-notice');
    const loggedFieldsEl = document.getElementById('feedback-logged-fields');
    const starRatingEl = document.getElementById('star-rating');
    const commentEl = document.getElementById('feedback-comment');
    const btnSend = document.getElementById('btn-feedback-send');
    const validationEl = document.getElementById('feedback-validation-msg');

    let feedbackStars = null;

    function hideValidation() {
        if (validationEl) {
            validationEl.hidden = true;
            validationEl.textContent = '';
        }
    }

    function resetForm() {
        feedbackStars = null;
        hideValidation();
        if (commentEl) commentEl.value = '';
        if (starRatingEl) {
            starRatingEl.querySelectorAll('.btn-star').forEach(b => b.classList.remove('is-on'));
        }
    }

    function configureModal() {
        if (!loginNoticeEl || !loggedFieldsEl || !btnSend) return;
        if (canSend) {
            loginNoticeEl.hidden = true;
            loggedFieldsEl.hidden = false;
            btnSend.hidden = false;
        } else {
            loginNoticeEl.hidden = false;
            loggedFieldsEl.hidden = true;
            btnSend.hidden = true;
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    window.showPostGameFeedbackModal = function (labelForUi, temaForApi) {
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
        resetForm();
        configureModal();
        const label = (labelForUi && String(labelForUi).trim()) || 'esta temática';
        if (leadEl) {
            leadEl.innerHTML =
                `Un segundo: tu opinión sobre <strong>${escapeHtml(label)}</strong> ayuda a mejorar las pistas y el juego. ` +
                'Puedes cerrar u <strong>Omitir</strong> sin enviar nada.';
        }
        const temaTrim =
            temaForApi != null && String(temaForApi).trim() !== '' ? String(temaForApi).trim() : '';
        modalEl.dataset.feedbackTema = temaTrim;

        const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
        inst.show();
    };

    if (starRatingEl) {
        starRatingEl.addEventListener('click', e => {
            const btn = e.target.closest('.btn-star');
            if (!btn || !starRatingEl.contains(btn)) return;
            const val = parseInt(btn.getAttribute('data-value'), 10);
            if (!Number.isFinite(val) || val < 1 || val > 5) return;
            feedbackStars = val;
            hideValidation();
            starRatingEl.querySelectorAll('.btn-star').forEach(b => {
                const n = parseInt(b.getAttribute('data-value'), 10);
                b.classList.toggle('is-on', n <= val);
            });
        });
    }

    if (btnSend) {
        btnSend.addEventListener('click', async () => {
            if (!canSend || !modalEl) return;
            hideValidation();
            const comment = commentEl && commentEl.value ? commentEl.value.trim() : '';
            if (feedbackStars === null && comment === '') {
                if (validationEl) {
                    validationEl.textContent =
                        'Selecciona una puntuación en estrellas o escribe un comentario antes de enviar.';
                    validationEl.hidden = false;
                }
                return;
            }
            const temaRaw = modalEl.dataset.feedbackTema || '';
            const temaPayload = temaRaw !== '' ? temaRaw : null;

            btnSend.disabled = true;
            try {
                const resp = await fetch('../api/feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        estrellas: feedbackStars,
                        comentario: comment,
                        tema: temaPayload,
                    }),
                });
                const raw = await resp.text();
                let data = null;
                try {
                    data = raw ? JSON.parse(raw) : null;
                } catch (_) {
                    data = null;
                }
                if (!resp.ok) {
                    const msg =
                        data && typeof data.error === 'string'
                            ? data.error
                            : `Error del servidor (${resp.status}).`;
                    if (validationEl) {
                        validationEl.textContent = msg;
                        validationEl.hidden = false;
                    }
                    return;
                }
                const inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) inst.hide();
            } catch (_) {
                if (validationEl) {
                    validationEl.textContent = 'No se pudo enviar. Inténtalo más tarde.';
                    validationEl.hidden = false;
                }
            } finally {
                btnSend.disabled = false;
            }
        });
    }

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', () => {
            hideValidation();
        });
    }
})();
