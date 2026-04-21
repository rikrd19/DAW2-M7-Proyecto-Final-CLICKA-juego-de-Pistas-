<?php
/**
 * User Profile Page.
 * Adapted from previous project logic with new CLICKA aesthetics.
 */
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once dirname(__DIR__) . '/api/lib/dicebear.php';

// Access control: ensures user is logged in
check_access();

$sessionUserId = $_SESSION['usuari_id'];
$sessionRole = $_SESSION['rol'];

// Target user: Admins can edit anyone, players only themselves
$targetId = $sessionUserId;
if (isset($_GET['id']) && is_numeric($_GET['id']) && $sessionRole === 'admin') {
    $targetId = (int)$_GET['id'];
}

// Fetch current user data
$stmt = $db->prepare("SELECT id, username, rol, foto FROM usuarios WHERE id = :id");
$stmt->bindValue(':id', $targetId, SQLITE3_INTEGER);
$res = $stmt->execute();
$user = $res->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    die("Error: Usuario no encontrado.");
}

$pageTitle = 'Editar Perfil';

// Image path logic: use custom SVG if default, or storage if uploaded
$photoUrl = ($user['foto'] === 'default.png' || !$user['foto']) 
    ? BASE_URL . "/assets/images/social_media/profile.svg"
    : (str_starts_with($user['foto'], 'http') ? $user['foto'] : BASE_URL . "/storage/uploads/" . $user['foto']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include '../includes/head.php'; ?>
    <style>
        .profile-card { max-width: 600px; margin: 40px auto; }
        .avatar-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--clika-accent); }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/menu.php'; ?>

    <main class="container">
        <div class="profile-card">
            <div class="card border-0 shadow-sm p-4">
                <div class="text-center mb-4">
                    <img src="<?php echo $photoUrl; ?>" alt="Avatar" class="avatar-preview mb-3">
                    <h2 class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <span class="badge bg-primary rounded-pill uppercase"><?php echo strtoupper($user['rol']); ?></span>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success text-center py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_avatar_url'): ?>
                    <div class="alert alert-danger text-center py-2">El avatar seleccionado no es válido. Elige otra opción.</div>
                <?php endif; ?>

                <form action="../processes/profile.proc.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="target_id" value="<?php echo $user['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de Usuario</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Nueva Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                        <div class="form-text">Dejar en blanco para mantener la actual.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">O elige un avatar del juego</label>
                        <div class="d-flex flex-wrap gap-2 p-2 bg-light rounded border">
                            <?php
                            $bust = bin2hex(random_bytes(4));
                            foreach (dicebear_quick_styles() as $style):
                                $url = dicebear_avatar_url($style, $user['username'], ['cb' => $bust]);
                            ?>
                                <div class="avatar-option" role="button" tabindex="0" onclick="pickAvatarFromTile(this)">
                                    <img src="<?php echo htmlspecialchars($url); ?>" alt="" class="rounded-circle border" style="width: 50px; cursor: pointer;">
                                </div>
                            <?php endforeach; ?>
                            <button type="button" id="openAvatarGalleryBtn"
                                    class="rounded-circle avatar-dicebear-more"
                                    title="Ver más avatares">
                                +
                            </button>
                        </div>
                        <input type="hidden" name="selected_avatar" id="selected_avatar">
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label fw-bold">O sube tu propia foto</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                    </div>

                    <?php if ($user['foto'] !== 'default.png'): ?>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="delete_photo" id="delPhoto">
                        <label class="form-check-label text-danger" for="delPhoto">
                            Eliminar foto actual y volver al avatar del juego
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-accent py-2 fw-bold">GUARDAR CAMBIOS</button>
                        <a href="../index.php" class="btn btn-link text-muted">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Modal: Galería de Avatares -->
    <div class="modal fade" id="avatarGallery" tabindex="-1" aria-labelledby="avatarGalleryLabel" aria-hidden="true"
         data-target-user-id="<?php echo (int) $user['id']; ?>">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="avatarGalleryLabel">Elige tu Avatar del Juego</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-3">La galería se genera al abrir esta ventana (petición al backend). Puedes pedir otra tanda sin cerrar.</p>
            <div id="avatarGalleryStatus" class="small text-muted mb-2" aria-live="polite"></div>
            <div class="row g-3 text-center" id="avatarGalleryGrid"></div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshDicebearGalleryBtn">Otra tanda aleatoria</button>
            <button type="button" class="btn btn-accent btn-sm" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script>
        (function () {
            // Resolve API from this page path (works with any vhost/docroot; BASE_URL alone often 404s).
            function galleryRequestUrl() {
                var modal = document.getElementById('avatarGallery');
                var uid = modal ? modal.getAttribute('data-target-user-id') : '';
                var u = new URL('../api/dicebear_gallery.php', window.location.href);
                if (uid) {
                    u.searchParams.set('user_id', uid);
                }
                return u.toString();
            }

            function renderGalleryItems(items) {
                var grid = document.getElementById('avatarGalleryGrid');
                if (!grid) return;
                grid.innerHTML = '';
                items.forEach(function (item) {
                    var col = document.createElement('div');
                    col.className = 'col-3 col-md-2';
                    var wrap = document.createElement('div');
                    wrap.className = 'avatar-option';
                    wrap.setAttribute('role', 'button');
                    wrap.tabIndex = 0;
                    wrap.addEventListener('click', function () {
                        window.pickAvatarFromTile(wrap);
                    });
                    var img = document.createElement('img');
                    img.className = 'rounded-circle border w-100 dicebear-gallery-tile';
                    img.style.cursor = 'pointer';
                    img.style.aspectRatio = '1/1';
                    img.alt = '';
                    img.src = item.url;
                    img.setAttribute('data-dicebear-style', item.style);
                    var small = document.createElement('small');
                    small.className = 'text-muted d-block mt-1';
                    small.style.fontSize = '0.6rem';
                    small.textContent = item.style;
                    wrap.appendChild(img);
                    wrap.appendChild(small);
                    col.appendChild(wrap);
                    grid.appendChild(col);
                });
            }

            function loadDicebearGallery() {
                var status = document.getElementById('avatarGalleryStatus');
                if (status) status.textContent = 'Cargando galería…';
                fetch(galleryRequestUrl(), {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    cache: 'no-store'
                })
                    .then(function (res) {
                        return res.text().then(function (text) {
                            var data;
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                throw new Error('Respuesta no JSON (¿404 del servidor?). Primeros caracteres: ' + text.slice(0, 120));
                            }
                            if (!res.ok || !data.ok) {
                                throw new Error((data && data.error) || ('HTTP ' + res.status));
                            }
                            if (!Array.isArray(data.items)) {
                                throw new Error('Formato inesperado del servidor.');
                            }
                            return data.items;
                        });
                    })
                    .then(function (items) {
                        renderGalleryItems(items);
                        if (status) status.textContent = '';
                    })
                    .catch(function (err) {
                        console.error('[dicebear_gallery]', err);
                        if (status) {
                            status.textContent = err && err.message ? err.message : 'Error al cargar la galería.';
                        }
                    });
            }

            window.pickAvatarFromTile = function (tile) {
                var img = tile.querySelector('img');
                if (!img) return;
                var url = img.currentSrc || img.src;
                document.querySelector('.avatar-preview').src = url;
                document.getElementById('selected_avatar').value = url;
                document.querySelectorAll('.avatar-option img').forEach(function (im) {
                    im.classList.remove('border-primary', 'border-3');
                });
                img.classList.add('border-primary', 'border-3');
                var modalEl = document.getElementById('avatarGallery');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance) instance.hide();
                }
            };

            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('avatarGallery');
                var openBtn = document.getElementById('openAvatarGalleryBtn');
                if (openBtn && modalEl) {
                    openBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof bootstrap === 'undefined') {
                            console.error('Bootstrap no está cargado (revisa includes/foot.php o la red).');
                            return;
                        }
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    });
                }
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', function () {
                        loadDicebearGallery();
                    });
                }
                var regenBtn = document.getElementById('refreshDicebearGalleryBtn');
                if (regenBtn) {
                    regenBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        loadDicebearGallery();
                    });
                }
            });
        })();
    </script>

    <?php include '../includes/foot.php'; ?>
