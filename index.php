<?php
/*  Pequeña comprobación de dependencias al arranque. */
require_once __DIR__ . '/config/auth.php';
$usuarioActual = usuarioActual();

$errores = [];
if (!class_exists('PDO')) {
    $errores[] = 'Falta la extensión PHP PDO.';
}
if (!in_array('mysql', PDO::getAvailableDrivers())) {
    $errores[] = 'Falta el driver PDO MySQL.';
}
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    $errores[] = 'No se encontró el archivo .env. Copia .env.example como .env.';
}
if (!is_dir(__DIR__ . '/config') || !is_dir(__DIR__ . '/api')) {
    $errores[] = 'La estructura del proyecto está incompleta (faltan config/ o api/).';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>MyGTS</title>
  <?php if (!empty($errores)): ?>
  <style>
    #php-errors { position:fixed; top:0;left:0;right:0;z-index:9999;background:#d32f2f;color:#fff;padding:12px 16px;font-family:monospace;font-size:13px; }
    #php-errors strong { display:block; margin-bottom:4px; }
    #php-errors ul { margin:0;padding-left:20px; }
  </style>
  <?php endif; ?>
  <!-- Bootstrap 5.3.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <!-- CSS personalizado -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php if (!empty($errores)): ?>
<div id="php-errors">
  <strong><i class="icon-triangle-alert"></i> Errores de configuración PHP:</strong>
  <ul><?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- ═══════════════════════════ MAPA ═══════════════════════════ -->
<div id="map"></div>

<!-- ═══════════════════════════ TOP BAR ═══════════════════════════ -->
<div id="topbar" class="position-fixed top-0 start-0 end-0 d-flex align-items-center gap-2 px-2 px-sm-3 py-2 bg-white bg-opacity-95 shadow-sm">
  <!-- Hamburguesa -->
  <button class="btn btn-link p-1 p-sm-2 text-secondary border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#leftOffcanvas">
    <i class="icon-list fs-3 fs-sm-4"></i>
  </button>

  <!-- Search -->
  <div class="flex-grow-1 position-relative">
    <i class="icon-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="z-index:3;"></i>
    <input type="text" class="form-control rounded-pill ps-5 bg-light border-0" placeholder="Buscar destino..." style="font-size:13px;font-size:clamp(13px, 1.2vw, 16px);">
  </div>

  <!-- Transport menu -->
  <button class="btn btn-link p-1 p-sm-2 text-secondary border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#rightOffcanvas">
    <i class="icon-ellipsis-vertical fs-3 fs-sm-4"></i>
  </button>
</div>

<!-- ═══════════════════════════ BANNER MODO SELECCIÓN EN MAPA ═══════════════════════════ -->
<div id="pickBanner" class="position-fixed start-50 translate-middle-x d-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white bg-opacity-95 shadow-sm border border-success d-none" style="top:132px;z-index:1100;">
  <i class="icon-map-pin-plus text-success"></i>
  <span class="small fw-semibold" id="pickBannerText">Toca el mapa para marcar el origen</span>
  <button type="button" class="btn btn-link btn-sm p-0 text-danger border-0" id="pickBannerCancel" title="Cancelar"><i class="icon-x"></i></button>
</div>

<!-- ═══════════════════════════ GPS INDICATOR (debajo del buscador) ═══════════════════════════ -->
<div id="gps-indicator" class="position-fixed start-50 translate-middle-x d-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-95 shadow-sm">
  <span class="d-flex align-items-center gap-1" style="height:14px;">
    <span class="bar" style="height:5px;"></span>
    <span class="bar" style="height:8px;"></span>
    <span class="bar" style="height:11px;"></span>
    <span class="bar" style="height:14px;"></span>
  </span>
  <span class="d-none d-sm-inline">GPS activo - Guatemala</span>
  <span class="d-inline d-sm-none">GPS</span>
</div>

<!-- ═══════════════════════════ COMPASS ═══════════════════════════ -->
<button id="compass" class="btn btn-light rounded-circle shadow-sm position-fixed border-0 p-0 d-flex align-items-center justify-content-center">
  <svg width="20" height="20" viewBox="0 0 24 24" class="w-75 h-75">
    <circle cx="12" cy="12" r="11" fill="none" stroke="#e0e0e0" stroke-width="1.5"/>
    <polygon points="12,4 14,12 12,11 10,12" fill="#ea4335"/>
    <polygon points="12,20 14,12 12,13 10,12" fill="#9e9e9e"/>
    <text x="12" y="3.5" text-anchor="middle" font-size="3" font-weight="bold" fill="#ea4335">N</text>
  </svg>
</button>

<!-- ═══════════════════════════ ZOOM CONTROLS ═══════════════════════════ -->
<div id="zoom-controls" class="position-fixed d-flex flex-column gap-1">
  <button class="zoom-btn btn btn-light bg-white bg-opacity-95 rounded-3 border-0 shadow-sm d-flex align-items-center justify-content-center fw-light">+</button>
  <button class="zoom-btn btn btn-light bg-white bg-opacity-95 rounded-3 border-0 shadow-sm d-flex align-items-center justify-content-center fw-light">−</button>
</div>

<!-- ═══════════════════════════ SCALE BAR ═══════════════════════════ -->
<div id="scale-bar" class="position-fixed d-flex align-items-center gap-2">
  <div class="bar-line"></div>
  <span>200 m</span>
</div>

<!-- ═══════════════════════════ LEFT OFFCANVAS ═══════════════════════════ -->
<div class="offcanvas offcanvas-start border-0" tabindex="-1" id="leftOffcanvas" style="width:280px;max-width:85vw;z-index:400;">
  <div class="offcanvas-header text-white pb-4 encabezado">
    <div class="d-flex align-items-center gap-3 w-100">
      <div class="avatar-circle d-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-25 border border-white border-opacity-50" style="width:44px;height:44px;font-size:18px;"><i class="icon-user fs-5"></i></div>
      <div class="flex-grow-1">
        <?php if ($usuarioActual): ?>
          <div class="fw-bold fs-6"><?= htmlspecialchars($usuarioActual['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="small opacity-75"><?= htmlspecialchars($usuarioActual['correo'], ENT_QUOTES, 'UTF-8') ?></div>
          <?php if ($usuarioActual['rol'] === 'admin'): ?>
            <span class="badge bg-warning text-dark mt-1"><i class="icon-lock me-1"></i>Administrador</span>
          <?php endif; ?>
        <?php else: ?>
          <div class="fw-bold fs-6">Mi Perfil</div>
          <div class="small opacity-75">Inicia sesión para guardar rutas</div>
        <?php endif; ?>
      </div>
    </div>
    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body p-0">
    <div class="mx-3 mb-3 p-3 rounded-3 border border-primary bg-primary bg-opacity-10">
      <div class="d-flex align-items-center gap-2 mb-1">
        <i class="icon-map-pin text-primary"></i>
        <span class="small fw-semibold">Confirmar ubicación</span>
      </div>
      <div class="small text-muted">14.6349°N, 90.5069°W - Guatemala</div>
    </div>

    <div class="px-3 pb-1 small text-uppercase text-muted fw-semibold" style="letter-spacing:.07em;">Navegación</div>
    <ul class="list-unstyled px-2 mb-2">
      <li>
        <a href="#" id="menu-inicio-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
          <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:32px;height:32px;font-size:14px;"><i class="icon-home"></i></div>
          <span class="small fw-medium">Inicio</span>
          <i class="icon-chevron-right ms-auto text-muted small"></i>
        </a>
      </li>
      <li>
        <a href="#" id="menu-mis-rutas-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
          <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width:32px;height:32px;font-size:14px;"><i class="icon-map"></i></div>
          <span class="small fw-medium">Mis rutas</span>
          <i class="icon-chevron-right ms-auto text-muted small"></i>
        </a>
      </li>
      <li>
        <a href="#" id="menu-historial-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
          <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 text-danger" style="width:32px;height:32px;font-size:14px;"><i class="icon-history"></i></div>
          <span class="small fw-medium">Historial</span>
          <i class="icon-chevron-right ms-auto text-muted small"></i>
        </a>
      </li>
    </ul>

    <hr class="my-2">

    <div class="px-3 pb-1 small text-uppercase text-muted fw-semibold" style="letter-spacing:.07em;">Ubicación</div>
    <ul class="list-unstyled px-2 mb-2">
      <li>
        <a href="#" id="menu-gps-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
          <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning" style="width:32px;height:32px;font-size:14px;"><i class="icon-crosshair"></i></div>
          <span class="small fw-medium">Obtener ubicación GPS</span>
          <i class="icon-chevron-right ms-auto text-muted small"></i>
        </a>
      </li>
      <li>
        <a href="#" id="menu-share-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
          <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:32px;height:32px;font-size:14px;"><i class="icon-share"></i></div>
          <span class="small fw-medium">Compartir ubicación</span>
          <i class="icon-chevron-right ms-auto text-muted small"></i>
        </a>
      </li>
    </ul>

    <hr class="my-2">

    <div class="px-3 pb-1 small text-uppercase text-muted fw-semibold" style="letter-spacing:.07em;">App</div>
    <ul class="list-unstyled px-2 mb-2">
      <li>
        <a href="#" id="menu-config-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
          <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-purple bg-opacity-10 text-purple" style="width:32px;height:32px;font-size:14px;"><i class="icon-settings"></i></div>
          <span class="small fw-medium">Configuración</span>
          <i class="icon-chevron-right ms-auto text-muted small"></i>
        </a>
      </li>
    </ul>

    <hr class="my-2">

    <div class="px-3 pb-1 small text-uppercase text-muted fw-semibold" style="letter-spacing:.07em;">Cuenta</div>
    <ul class="list-unstyled px-2 mb-2">
      <?php if ($usuarioActual): ?>
        <?php if ($usuarioActual['rol'] === 'admin'): ?>
        <li>
          <a href="admin.php" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark">
            <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning" style="width:32px;height:32px;font-size:14px;"><i class="icon-lock"></i></div>
            <span class="small fw-medium">Panel de administración</span>
            <i class="icon-chevron-right ms-auto text-muted small"></i>
          </a>
        </li>
        <?php endif; ?>
        <li>
          <a href="#" id="menu-logout-btn" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark" data-bs-dismiss="offcanvas">
            <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 text-danger" style="width:32px;height:32px;font-size:14px;"><i class="icon-log-out"></i></div>
            <span class="small fw-medium">Cerrar sesión</span>
            <i class="icon-chevron-right ms-auto text-muted small"></i>
          </a>
        </li>
      <?php else: ?>
        <li>
          <a href="login.php" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark">
            <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:32px;height:32px;font-size:14px;"><i class="icon-log-in"></i></div>
            <span class="small fw-medium">Iniciar sesión</span>
            <i class="icon-chevron-right ms-auto text-muted small"></i>
          </a>
        </li>
        <li>
          <a href="registro.php" class="d-flex align-items-center gap-3 px-2 py-2 rounded-3 text-decoration-none text-dark">
            <div class="menu-icon d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width:32px;height:32px;font-size:14px;"><i class="icon-user-plus"></i></div>
            <span class="small fw-medium">Crear cuenta</span>
            <i class="icon-chevron-right ms-auto text-muted small"></i>
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- ═══════════════════════════ RIGHT OFFCANVAS ═══════════════════════════ -->
<div class="offcanvas offcanvas-end border-0" tabindex="-1" id="rightOffcanvas" style="width:340px;max-width:90vw;z-index:400;">
  <div class="offcanvas-header border-bottom">
    <h6 class="mb-0 fw-bold">Cómo llegar</h6>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body p-0">
    <div class="px-3 pt-3 pb-2">
      <div class="mb-2">
        <label class="form-label small text-muted fw-semibold mb-1">Origen</label>
        <div class="position-relative">
          <span class="position-absolute top-50 start-0 translate-middle-y ms-3 rounded-circle" style="width:8px;height:8px;background-color:#198754;z-index:3;"></span>
          <input type="text" class="form-control form-control-sm rounded-pill ps-5 bg-light border-0" id="origenInput" placeholder="Mi ubicación" autocomplete="off">
          <button type="button" class="btn btn-link text-decoration-none position-absolute top-50 translate-middle-y p-1" id="origenMapBtn" title="Marcar origen en el mapa" style="right:40px;z-index:4;">
            <i class="icon-map-pin-plus text-success" style="font-size:15px;"></i>
          </button>
          <div class="autocomplete-list position-absolute top-100 start-0 end-0 bg-white border rounded-3 shadow-sm mt-1 d-none" id="origenAutocomplete" style="z-index:10;max-height:200px;overflow-y:auto;"></div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small text-muted fw-semibold mb-1">Destino</label>
        <div class="position-relative">
          <span class="position-absolute top-50 start-0 translate-middle-y ms-3 rounded-circle" style="width:8px;height:8px;background-color:#dc3545;z-index:3;"></span>
          <input type="text" class="form-control form-control-sm rounded-pill ps-5 bg-light border-0" id="destinoInput" placeholder="¿A dónde vas?" autocomplete="off">
          <button type="button" class="btn btn-link text-decoration-none position-absolute top-50 end-0 translate-middle-y p-1 pe-3" id="destinoMapBtn" title="Marcar destino en el mapa" style="z-index:4;">
            <i class="icon-map-pin-plus text-danger" style="font-size:15px;"></i>
          </button>
          <div class="autocomplete-list position-absolute top-100 start-0 end-0 bg-white border rounded-3 shadow-sm mt-1 d-none" id="destinoAutocomplete" style="z-index:10;max-height:200px;overflow-y:auto;"></div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 mb-3">
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" id="evitarInseguro" checked>
          <label class="form-check-label small" for="evitarInseguro">Evitar zonas inseguras</label>
        </div>
        <div class="ms-auto">
          <select class="form-select form-select-sm border-0 bg-light" id="transporteSelect">
            <option value="todos">Todos</option>
            <option value="Bus">Bus</option>
            <option value="Transmetro">Transmetro</option>
          </select>
        </div>
      </div>

      <button class="btn btn-primary w-100 rounded-pill fw-semibold mb-3" id="calcularRutaBtn" disabled>
        <i class="icon-circle-arrow-right me-1"></i>Calcular ruta
      </button>

      <div id="routeResults" class="d-none">
        <hr>
        <div id="routeSummary" class="mb-2"></div>
        <div id="routeTramos"></div>
      </div>

      <div id="routeError" class="d-none">
        <div class="alert alert-warning small py-2 mb-0" id="routeErrorMsg"></div>
      </div>
    </div>

    <hr class="my-1">
    <div class="px-3 py-2">
      <div class="small text-uppercase text-muted fw-semibold mb-2" style="letter-spacing:.07em;">Paradas</div>
      <div id="busPanel" class="px-0"></div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════ BOTTOM SHEET ═══════════════════════════ -->
<div id="bottom-sheet" class="position-fixed bottom-0 start-0 end-0 bg-white rounded-top-4 shadow-lg">
  <div class="d-flex justify-content-center pt-2">
    <div class="bs-handle rounded-2 bg-secondary bg-opacity-25"></div>
  </div>

  <div class="px-2 px-sm-3 pb-1">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div>
        <div class="fw-bold" style="font-size:clamp(14px, 2vw, 18px);">Mi ubicación actual</div>
        <div class="small text-muted">Ciudad de Guatemala, Guatemala</div>
      </div>
      <span class="badge rounded-pill bg-success bg-opacity-10 text-success">
        <i class="icon-circle-check me-1"></i>Alta
      </span>
    </div>

    <div class="row g-1 g-sm-2 mb-3">
      <div class="col-4">
        <div class="coord-card bg-light rounded-3 p-1 p-sm-2 text-center">
          <div class="coord-label small text-muted" style="font-size:clamp(8px, 1vw, 10px);">Latitud</div>
          <div class="coord-val fw-bold" data-coord="lat" style="font-size:clamp(11px, 1.2vw, 13px);">14.6349</div>
        </div>
      </div>
      <div class="col-4">
        <div class="coord-card bg-light rounded-3 p-1 p-sm-2 text-center">
          <div class="coord-label small text-muted" style="font-size:clamp(8px, 1vw, 10px);">Longitud</div>
          <div class="coord-val fw-bold" data-coord="lng" style="font-size:clamp(11px, 1.2vw, 13px);">-90.5069</div>
        </div>
      </div>
      <div class="col-4">
        <div class="coord-card bg-light rounded-3 p-1 p-sm-2 text-center">
          <div class="coord-label small text-muted" style="font-size:clamp(8px, 1vw, 10px);">Precisión</div>
          <div class="coord-val fw-bold" data-coord="acc" style="font-size:clamp(11px, 1.2vw, 13px);">±5 m</div>
        </div>
      </div>
    </div>

    <div class="row g-1 g-sm-2 pb-3">
      <div class="col-4">
        <button type="button" id="btn-localizar" class="btn w-100 fw-semibold small rounded-3 py-1 py-sm-2 localizar" style="font-size:clamp(10px, 1vw, 13px);">
          <i class="icon-crosshair d-block fs-6 fs-sm-5 mb-1"></i>Localizar
        </button>
      </div>
      <div class="col-4">
        <button class="btn btn-light w-100 fw-semibold small rounded-3 border py-1 py-sm-2" style="font-size:clamp(10px, 1vw, 13px);">
          <i class="icon-rotate-cw d-block fs-6 fs-sm-5 mb-1"></i>Actualizar
        </button>
      </div>
      <div class="col-4">
        <button type="button" id="btn-guardar" class="btn w-100 fw-semibold small rounded-3 py-1 py-sm-2 guardar" style="font-size:clamp(10px, 1vw, 13px);">
          <i class="icon-bookmark d-block fs-6 fs-sm-5 mb-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════ LOADING OVERLAY ═══════════════════════════ -->
<!-- ═══════════════════════════ ASISTENTE IA FLOATING CHAT ═══════════════════════════ -->
<button id="ai-chat-toggle" class="btn btn-primary rounded-circle shadow-lg position-fixed d-flex align-items-center justify-content-center" style="bottom: 250px; left: 16px; z-index: 990;" title="Chat con Gity" aria-label="Abrir chat con Gity">
  <i class="icon-bot fs-4"></i>
</button>

<div id="ai-chat-card" class="card shadow-lg position-fixed border-0 d-none" style="bottom: 260px; left: 16px; z-index: 995;">
  <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between py-2 px-3">
    <div class="d-flex align-items-center gap-2">
      <i class="icon-bot fs-5"></i>
      <span class="fw-semibold small">Gity</span>
    </div>
    <button type="button" class="btn-close btn-close-white small" id="ai-chat-close" aria-label="Close"></button>
  </div>
  
  <div class="card-body p-3 overflow-auto bg-light" id="ai-chat-messages" style="display: flex; flex-direction: column; gap: 10px;">
    <div class="p-2 rounded-3 bg-white text-dark shadow-sm small align-self-start border" style="max-width: 85%;">
      ¡Hola! Soy Gity, tu asistente de movilidad en Ciudad de Guatemala. Pregúntame sobre rutas, paradas o cómo moverte.
    </div>
  </div>

  <div class="card-footer bg-white p-2 border-top">
    <form id="ai-chat-form" class="d-flex gap-2">
      <input type="text" id="ai-chat-input" class="form-control form-control-sm rounded-pill border-0 bg-light px-3" placeholder="Escribe tu consulta..." required autocomplete="off">
      <button type="submit" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
        <i class="icon-send" style="font-size: 11px;"></i>
      </button>
    </form>
  </div>
</div>

<div id="routeLoading" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="z-index:1200;background:rgba(255,255,255,0.6);backdrop-filter:blur(2px);">
  <div class="position-absolute top-50 start-50 translate-middle text-center">
    <div class="spinner-border text-primary mb-2" role="status" style="width:2.5rem;height:2.5rem;"></div>
    <div class="small fw-semibold text-muted">Calculando ruta segura...</div>
  </div>
</div>

<!-- ═══════════════════════════ TOAST UNIFICADO ═══════════════════════════ -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 1100;">
  <div id="appToast" class="toast align-items-center text-bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body small fw-medium" id="toast-msg">
        Bienvenido a Guatemala
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>


<!-- ═══════════════════════════ MODAL MIS RUTAS ═══════════════════════════ -->
<div class="modal fade" id="misRutasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="icon-map me-2 text-primary"></i>Mis rutas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-2" id="misRutasList">
        <div class="text-center text-muted small py-4">Cargando...</div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════ MODAL HISTORIAL ═══════════════════════════ -->
<div class="modal fade" id="historialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="icon-history me-2 text-danger"></i>Historial</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-2" id="historialList"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" id="btnBorrarHistorial">
          <i class="icon-trash-2 me-1"></i>Borrar historial
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════ MODAL CONFIGURACIÓN ═══════════════════════════ -->
<div class="modal fade" id="configModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="icon-settings me-2 text-primary"></i>Configuración</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">

        <div class="small text-uppercase text-muted fw-semibold mb-2" style="letter-spacing:.07em;">Preferencias de ruta</div>
        <div class="mb-3">
          <label class="form-label small fw-medium mb-1" for="configTransporte">Transporte por defecto</label>
          <select class="form-select form-select-sm border-0 bg-light" id="configTransporte">
            <option value="todos">Todos</option>
            <option value="Bus">Bus</option>
            <option value="Transmetro">Transmetro</option>
          </select>
        </div>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="configEvitarInseguro" checked>
          <label class="form-check-label small" for="configEvitarInseguro">Evitar zonas inseguras</label>
        </div>

        <hr class="my-3">

        <div class="small text-uppercase text-muted fw-semibold mb-2" style="letter-spacing:.07em;">Mapa</div>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="configVerParadas" checked>
          <label class="form-check-label small" for="configVerParadas">Mostrar paradas en el mapa</label>
        </div>

        <hr class="my-3">

        <div class="small text-uppercase text-muted fw-semibold mb-2" style="letter-spacing:.07em;">Datos</div>
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="small fw-medium">Limpiar historial local</div>
            <div class="text-muted small">Borra las rutas guardadas en este dispositivo</div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" id="btnConfigLimpiarDatos">
            <i class="icon-trash-2 me-1"></i>Limpiar
          </button>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btnConfigReset">
          <i class="icon-rotate-ccw me-1"></i>Restablecer
        </button>
        <button type="button" class="btn btn-sm btn-primary rounded-pill" data-bs-dismiss="modal">Listo</button>
      </div>
    </div>
  </div>
</div>
<!-- Bootstrap 5.3.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  window.MYGTS_USUARIO = <?= $usuarioActual
      ? json_encode([
          'id'     => (int)$usuarioActual['id'],
          'nombre' => $usuarioActual['nombre'],
          'rol'    => $usuarioActual['rol'],
      ], JSON_UNESCAPED_UNICODE)
      : 'null' ?>;
</script>
<!-- App JS -->
<script src="js/main.js"></script>

</body>
</html>