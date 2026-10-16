<?php
require_once __DIR__ . '/config/auth.php';

$usuario = requiereAdminPagina();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración — MyGTS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
  <style>
    body { background: #f1f5f9; }
    .sidebar-nav .nav-link {
      color: #334155;
      border-radius: 10px;
      font-weight: 500;
      font-size: 0.9rem;
      padding: 10px 14px;
      margin-bottom: 4px;
    }
    .sidebar-nav .nav-link:hover { background: #e2e8f0; }
    .sidebar-nav .nav-link.active { background: #059669; color: #fff; }
    .table td, .table th { vertical-align: middle; }
    .stat-card {
      border: 0;
      border-radius: 14px;
      color: #fff;
      box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .toast-container { z-index: 2000; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-dark navbar-dark px-3">
  <a class="navbar-brand fw-bold" href="admin.php"><i class="icon-settings me-2"></i>MyGTS Admin</a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <span class="navbar-text small me-2 d-none d-sm-inline">
      <i class="icon-circle-user me-1"></i><?= htmlspecialchars($usuario['nombre']) ?>
      <span class="badge bg-success ms-1">admin</span>
    </span>
    <a href="index.php" class="btn btn-outline-light btn-sm"><i class="icon-map me-1"></i>Volver al mapa</a>
    <button class="btn btn-danger btn-sm" id="btnLogout"><i class="icon-log-out me-1"></i>Salir</button>
  </div>
</nav>

<div class="container-fluid py-3">
  <div class="row">
    <div class="col-12 col-md-3 col-xl-2 mb-3">
      <div class="d-flex flex-column sidebar-nav" id="sidebarNav">
        <button class="nav-link text-start active" data-seccion="resumen"><i class="icon-gauge me-2"></i>Resumen</button>
        <button class="nav-link text-start" data-seccion="paradas"><i class="icon-signpost me-2"></i>Paradas</button>
        <button class="nav-link text-start" data-seccion="rutas"><i class="icon-bus me-2"></i>Rutas</button>
        <button class="nav-link text-start" data-seccion="puntos"><i class="icon-shield-check me-2"></i>Puntos de seguridad</button>
        <button class="nav-link text-start" data-seccion="usuarios"><i class="icon-users me-2"></i>Usuarios</button>
        <button class="nav-link text-start" data-seccion="reportes"><i class="icon-message-square-text me-2"></i>Reportes</button>
      </div>
    </div>

    <div class="col-12 col-md-9 col-xl-10">
      <section id="seccion-resumen"></section>
      <section id="seccion-paradas" class="d-none"></section>
      <section id="seccion-rutas" class="d-none"></section>
      <section id="seccion-puntos" class="d-none"></section>
      <section id="seccion-usuarios" class="d-none"></section>
      <section id="seccion-reportes" class="d-none"></section>
    </div>
  </div>
</div>

<!-- ═══ Modales ═══ -->
<div class="modal fade" id="formModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="formModalTitle">Nuevo registro</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="formModalBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnGuardarModal"><i class="icon-check me-1"></i>Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3">
  <div id="appToast" class="toast align-items-center text-bg-dark border-0 shadow">
    <div class="d-flex">
      <div class="toast-body small fw-medium" id="toastMsg"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ═══════════════ Utilidades ═══════════════
const $ = (sel) => document.querySelector(sel);
const modal = new bootstrap.Modal($('#formModal'));

function toast(mensaje) {
  $('#toastMsg').textContent = mensaje;
  bootstrap.Toast.getOrCreateInstance($('#appToast')).show();
}

async function api(url, opciones = {}) {
  const res = await fetch(url, opciones);
  const data = await res.json();
  if (data.status !== 'ok') throw new Error(data.mensaje || 'Error del servidor');
  return data.data ?? data;
}

function postJSON(url, body) {
  return api(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
}

function esc(texto) {
  const d = document.createElement('div');
  d.textContent = texto ?? '';
  return d.innerHTML;
}

const badges = {
  seguro:   'bg-success',
  moderado: 'bg-warning text-dark',
  inseguro: 'bg-danger',
};

function badgeTipo(tipo) {
  return `<span class="badge ${badges[tipo] || 'bg-secondary'}">${esc(tipo)}</span>`;
}

// ═══════════════ Navegación ═══════════════
const cargadores = {
  resumen: cargarResumen,
  paradas: cargarParadas,
  rutas: cargarRutas,
  puntos: cargarPuntos,
  usuarios: cargarUsuarios,
  reportes: cargarReportes,
};

document.querySelectorAll('#sidebarNav .nav-link').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#sidebarNav .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const seccion = btn.dataset.seccion;
    Object.keys(cargadores).forEach(k => {
      $('#seccion-' + k).classList.toggle('d-none', k !== seccion);
    });
    cargadores[seccion]();
  });
});

// ═══════════════ Resumen ═══════════════
async function cargarResumen() {
  const cont = $('#seccion-resumen');
  cont.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success"></div></div>';
  try {
    const d = await api('api/admin/resumen.php');
    const pt = d.puntos_por_tipo;
    const cards = [
      { icon: 'icon-users', label: 'Usuarios', valor: d.usuarios, color: '#059669', extra: d.usuarios_activos + ' activos' },
      { icon: 'icon-bus', label: 'Rutas activas', valor: d.rutas, color: '#0284c7', extra: '' },
      { icon: 'icon-signpost', label: 'Paradas activas', valor: d.paradas, color: '#7c3aed', extra: '' },
      { icon: 'icon-shield-check', label: 'Puntos de seguridad', valor: d.puntos_seguridad, color: '#f59e0b', extra: '' },
      { icon: 'icon-message-square-text', label: 'Reportes', valor: d.reportes, color: '#dc2626', extra: '' },
      { icon: 'icon-bookmark', label: 'Rutas guardadas', valor: d.rutas_guardadas, color: '#0f766e', extra: '' },
    ];
    cont.innerHTML = `
      <h5 class="fw-bold mb-3"><i class="icon-gauge me-2"></i>Resumen general</h5>
      <div class="row g-3 mb-4">
        ${cards.map(c => `
          <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card p-3 h-100" style="background:linear-gradient(135deg,${c.color},${c.color}dd);">
              <div class="d-flex align-items-center gap-2 mb-2"><i class="${c.icon} fs-5"></i><span class="small opacity-75">${c.label}</span></div>
              <div class="fs-3 fw-bold">${c.valor}</div>
              <div class="small opacity-75">${c.extra}</div>
            </div>
          </div>`).join('')}
      </div>
      <h6 class="fw-bold mb-2">Puntos de seguridad por tipo</h6>
      <div class="row g-3">
        <div class="col-4"><div class="card p-3 border-0 shadow-sm text-center"><div class="small text-muted">Seguros</div><div class="fs-3 fw-bold text-success">${pt.seguro}</div></div></div>
        <div class="col-4"><div class="card p-3 border-0 shadow-sm text-center"><div class="small text-muted">Moderados</div><div class="fs-3 fw-bold text-warning">${pt.moderado}</div></div></div>
        <div class="col-4"><div class="card p-3 border-0 shadow-sm text-center"><div class="small text-muted">Inseguros</div><div class="fs-3 fw-bold text-danger">${pt.inseguro}</div></div></div>
      </div>`;
  } catch (e) {
    cont.innerHTML = '<div class="alert alert-danger small">' + esc(e.message) + '</div>';
  }
}

// ═══════════════ Paradas ═══════════════
let paradasCache = [];

async function cargarParadas() {
  const cont = $('#seccion-paradas');
  cont.innerHTML = `
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      <h5 class="fw-bold mb-0 me-auto"><i class="icon-signpost me-2"></i>Paradas</h5>
      <input type="text" id="paradasSearch" class="form-control form-control-sm" style="max-width:240px;" placeholder="Buscar parada...">
      <button class="btn btn-success btn-sm" onclick="abrirParada()"><i class="icon-plus me-1"></i>Nueva parada</button>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
      <thead class="table-light small"><tr>
        <th>ID</th><th>Nombre</th><th>Dirección</th><th>Código</th><th>Coordenadas</th><th>Estado</th><th class="text-end">Acciones</th>
      </tr></thead><tbody id="paradasBody"></tbody></table></div></div>`;

  const input = $('#paradasSearch');
  input.addEventListener('input', () => renderParadas(paradasCache, input.value));

  try {
    paradasCache = await api('api/admin/paradas.php');
    renderParadas(paradasCache, '');
  } catch (e) { renderParadasError(cont, e); }
}

function renderParadasError(cont, e) {
  cont.innerHTML += '<div class="alert alert-danger small mt-3">' + esc(e.message) + '</div>';
}

function renderParadas(lista, q) {
  const tbody = $('#paradasBody');
  const filtradas = q ? lista.filter(p =>
    (p.nombre || '').toLowerCase().includes(q.toLowerCase()) ||
    (p.direccion || '').toLowerCase().includes(q.toLowerCase()) ||
    (p.codigo || '').toLowerCase().includes(q.toLowerCase())
  ) : lista;

  tbody.innerHTML = filtradas.length ? filtradas.map(p => `
    <tr class="${p.activa == 0 ? 'table-secondary opacity-75' : ''}">
      <td class="small">${p.id}</td>
      <td class="small fw-semibold">${esc(p.nombre)}</td>
      <td class="small">${esc(p.direccion)}</td>
      <td class="small">${esc(p.codigo)}</td>
      <td class="small text-muted">${p.latitud}, ${p.longitud}</td>
      <td>${p.activa == 1 ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>'}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-primary" onclick="abrirParada(${p.id})"><i class="icon-pencil"></i></button>
        <button class="btn btn-sm ${p.activa == 1 ? 'btn-outline-danger' : 'btn-outline-success'}" onclick="toggleParada(${p.id})">
          <i class="${p.activa == 1 ? 'icon-circle-x' : 'icon-circle-check'}"></i>
        </button>
      </td>
    </tr>`).join('') : '<tr><td colspan="7" class="text-center text-muted small py-4">Sin resultados</td></tr>';
}

function abrirParada(id = 0) {
  const p = paradasCache.find(x => x.id === id);
  $('#formModalTitle').textContent = p ? 'Editar parada' : 'Nueva parada';
  $('#formModalBody').innerHTML = `
    <input type="hidden" id="f_id" value="${p ? p.id : 0}">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label small fw-semibold">Nombre *</label><input id="f_nombre" class="form-control" value="${esc(p ? p.nombre : '')}"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Código</label><input id="f_codigo" class="form-control" value="${esc(p ? p.codigo : '')}"></div>
      <div class="col-12"><label class="form-label small fw-semibold">Dirección</label><input id="f_direccion" class="form-control" value="${esc(p ? p.direccion : '')}"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Latitud *</label><input id="f_latitud" type="number" step="any" class="form-control" value="${p ? p.latitud : ''}"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Longitud *</label><input id="f_longitud" type="number" step="any" class="form-control" value="${p ? p.longitud : ''}"></div>
      <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="f_activa" ${!p || p.activa == 1 ? 'checked' : ''}><label class="form-check-label small" for="f_activa">Parada activa</label></div></div>
    </div>`;
  modal.show();
}

async function guardarParada() {
  const body = {
    accion: 'guardar',
    id: parseInt($('#f_id').value) || 0,
    nombre: $('#f_nombre').value.trim(),
    codigo: $('#f_codigo').value.trim(),
    direccion: $('#f_direccion').value.trim(),
    latitud: parseFloat($('#f_latitud').value),
    longitud: parseFloat($('#f_longitud').value),
    activa: $('#f_activa').checked ? 1 : 0,
  };
  if (!body.nombre) return toast('El nombre es obligatorio.');
  if (isNaN(body.latitud) || isNaN(body.longitud)) return toast('Ingresa coordenadas válidas.');
  try {
    const r = await postJSON('api/admin/paradas.php', body);
    modal.hide();
    toast(r.mensaje || 'Guardado');
    cargarParadas();
  } catch (e) { toast(e.message); }
}

async function toggleParada(id) {
  const p = paradasCache.find(x => x.id === id);
  if (!p) return;
  try {
    await postJSON('api/admin/paradas.php', {
      accion: 'guardar',
      id: p.id,
      nombre: p.nombre,
      codigo: p.codigo,
      direccion: p.direccion,
      latitud: parseFloat(p.latitud),
      longitud: parseFloat(p.longitud),
      activa: p.activa == 1 ? 0 : 1,
    });
    toast(p.activa == 1 ? 'Parada desactivada' : 'Parada activada');
    cargarParadas();
  } catch (e) { toast(e.message); }
}

// ═══════════════ Rutas ═══════════════
let rutasCache = [];

async function cargarRutas() {
  const cont = $('#seccion-rutas');
  cont.innerHTML = `
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      <h5 class="fw-bold mb-0 me-auto"><i class="icon-bus me-2"></i>Rutas de transporte</h5>
      <button class="btn btn-success btn-sm" onclick="abrirRuta()"><i class="icon-plus me-1"></i>Nueva ruta</button>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
      <thead class="table-light small"><tr>
        <th>ID</th><th>Nombre</th><th>Tipo</th><th>Color</th><th>Paradas</th><th>Estado</th><th class="text-end">Acciones</th>
      </tr></thead><tbody id="rutasBody"></tbody></table></div></div>`;
  try {
    rutasCache = await api('api/admin/rutas.php');
    renderRutas();
  } catch (e) { renderRutasError(cont, e); }
}

function renderRutasError(cont, e) {
  cont.innerHTML += '<div class="alert alert-danger small mt-3">' + esc(e.message) + '</div>';
}

function renderRutas() {
  const tbody = $('#rutasBody');
  tbody.innerHTML = rutasCache.length ? rutasCache.map(r => `
    <tr class="${r.activa == 0 ? 'table-secondary opacity-75' : ''}">
      <td class="small">${r.id}</td>
      <td class="small fw-semibold">${esc(r.nombre)}</td>
      <td><span class="badge" style="background:${esc(r.color_mapa)}">${esc(r.tipo_transporte)}</span></td>
      <td><span class="d-inline-block rounded-circle" style="width:20px;height:20px;background:${esc(r.color_mapa)};"></span></td>
      <td class="small">${r.num_paradas}</td>
      <td>${r.activa == 1 ? '<span class="badge bg-success">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>'}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-primary" onclick="abrirRuta(${r.id})"><i class="icon-pencil"></i></button>
        <button class="btn btn-sm ${r.activa == 1 ? 'btn-outline-danger' : 'btn-outline-success'}" onclick="toggleRuta(${r.id})"><i class="${r.activa == 1 ? 'icon-circle-x' : 'icon-circle-check'}"></i></button>
      </td>
    </tr>`).join('') : '<tr><td colspan="7" class="text-center text-muted small py-4">Sin rutas registradas</td></tr>';
}

function abrirRuta(id = 0) {
  const r = rutasCache.find(x => x.id === id);
  $('#formModalTitle').textContent = r ? 'Editar ruta' : 'Nueva ruta';
  $('#formModalBody').innerHTML = `
    <input type="hidden" id="f_id" value="${r ? r.id : 0}">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label small fw-semibold">Nombre *</label><input id="f_nombre" class="form-control" value="${esc(r ? r.nombre : '')}"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Tipo de transporte</label>
        <select id="f_tipo" class="form-select">
          <option value="Bus" ${r && r.tipo_transporte === 'Bus' ? 'selected' : ''}>Bus</option>
          <option value="Transmetro" ${r && r.tipo_transporte === 'Transmetro' ? 'selected' : ''}>Transmetro</option>
        </select></div>
      <div class="col-12"><label class="form-label small fw-semibold">Descripción</label><input id="f_descripcion" class="form-control" value="${esc(r ? r.descripcion : '')}"></div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Color en el mapa</label><input id="f_color" type="color" class="form-control form-control-color" value="${esc(r ? r.color_mapa : '#1a73e8')}"></div>
      <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="f_activa" ${!r || r.activa == 1 ? 'checked' : ''}><label class="form-check-label small" for="f_activa">Ruta activa</label></div></div>
    </div>`;
  modal.show();
}

async function guardarRuta() {
  const body = {
    accion: 'guardar',
    id: parseInt($('#f_id').value) || 0,
    nombre: $('#f_nombre').value.trim(),
    descripcion: $('#f_descripcion').value.trim(),
    tipo_transporte: $('#f_tipo').value,
    color_mapa: $('#f_color').value,
    activa: $('#f_activa').checked ? 1 : 0,
  };
  if (!body.nombre) return toast('El nombre es obligatorio.');
  try {
    const r = await postJSON('api/admin/rutas.php', body);
    modal.hide();
    toast(r.mensaje || 'Guardado');
    cargarRutas();
  } catch (e) { toast(e.message); }
}

async function toggleRuta(id) {
  const r = rutasCache.find(x => x.id === id);
  if (!r) return;
  try {
    await postJSON('api/admin/rutas.php', {
      accion: 'guardar', id: r.id, nombre: r.nombre, descripcion: r.descripcion,
      tipo_transporte: r.tipo_transporte, color_mapa: r.color_mapa,
      activa: r.activa == 1 ? 0 : 1,
    });
    toast(r.activa == 1 ? 'Ruta desactivada' : 'Ruta activada');
    cargarRutas();
  } catch (e) { toast(e.message); }
}

// ═══════════════ Puntos de seguridad ═══════════════
let puntosCache = [];

async function cargarPuntos() {
  const cont = $('#seccion-puntos');
  cont.innerHTML = `
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
      <h5 class="fw-bold mb-0 me-auto"><i class="icon-shield-check me-2"></i>Puntos de seguridad</h5>
      <button class="btn btn-success btn-sm" onclick="abrirPunto()"><i class="icon-plus me-1"></i>Nuevo punto</button>
    </div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
      <thead class="table-light small"><tr>
        <th>ID</th><th>Tipo</th><th>Descripción</th><th>Fuente</th><th>Coordenadas</th><th>Estado</th><th class="text-end">Acciones</th>
      </tr></thead><tbody id="puntosBody"></tbody></table></div></div>`;
  try {
    puntosCache = await api('api/admin/puntos_seguridad.php');
    renderPuntos();
  } catch (e) { renderPuntosError(cont, e); }
}

function renderPuntosError(cont, e) {
  cont.innerHTML += '<div class="alert alert-danger small mt-3">' + esc(e.message) + '</div>';
}

function renderPuntos() {
  const tbody = $('#puntosBody');
  tbody.innerHTML = puntosCache.length ? puntosCache.map(p => `
    <tr class="${p.activo == 0 ? 'table-secondary opacity-75' : ''}">
      <td class="small">${p.id}</td>
      <td>${badgeTipo(p.tipo)}</td>
      <td class="small">${esc(p.descripcion)}</td>
      <td class="small text-muted">${esc(p.fuente)}</td>
      <td class="small text-muted">${p.latitud}, ${p.longitud}</td>
      <td>${p.activo == 1 ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-primary" onclick="abrirPunto(${p.id})"><i class="icon-pencil"></i></button>
        <button class="btn btn-sm ${p.activo == 1 ? 'btn-outline-danger' : 'btn-outline-success'}" onclick="togglePunto(${p.id})"><i class="${p.activo == 1 ? 'icon-circle-x' : 'icon-circle-check'}"></i></button>
      </td>
    </tr>`).join('') : '<tr><td colspan="7" class="text-center text-muted small py-4">Sin puntos registrados</td></tr>';
}

function abrirPunto(id = 0) {
  const p = puntosCache.find(x => x.id === id);
  $('#formModalTitle').textContent = p ? 'Editar punto' : 'Nuevo punto';
  $('#formModalBody').innerHTML = `
    <input type="hidden" id="f_id" value="${p ? p.id : 0}">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label small fw-semibold">Tipo *</label>
        <select id="f_tipo" class="form-select">
          <option value="seguro" ${p && p.tipo === 'seguro' ? 'selected' : ''}>Seguro</option>
          <option value="moderado" ${!p || p.tipo === 'moderado' ? 'selected' : ''}>Moderado</option>
          <option value="inseguro" ${p && p.tipo === 'inseguro' ? 'selected' : ''}>Inseguro</option>
        </select></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Fuente</label>
        <select id="f_fuente" class="form-select">
          <option value="curado" ${p && p.fuente === 'curado' ? 'selected' : ''}>Curado</option>
          <option value="reporte" ${!p || p.fuente === 'reporte' ? 'selected' : ''}>Reporte</option>
        </select></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Coordenadas (lat, lng)</label>
        <input id="f_coords" class="form-control" placeholder="14.6349, -90.5069" value="${p ? p.latitud + ', ' + p.longitud : ''}"></div>
      <div class="col-12"><label class="form-label small fw-semibold">Descripción *</label><textarea id="f_descripcion" rows="2" class="form-control">${esc(p ? p.descripcion : '')}</textarea></div>
      <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="f_activa" ${!p || p.activo == 1 ? 'checked' : ''}><label class="form-check-label small" for="f_activa">Punto activo</label></div></div>
    </div>`;
  modal.show();
}

async function guardarPunto() {
  const coords = $('#f_coords').value.split(',').map(s => parseFloat(s.trim()));
  const body = {
    accion: 'guardar',
    id: parseInt($('#f_id').value) || 0,
    tipo: $('#f_tipo').value,
    fuente: $('#f_fuente').value,
    descripcion: $('#f_descripcion').value.trim(),
    latitud: coords[0],
    longitud: coords[1],
    activo: $('#f_activa').checked ? 1 : 0,
  };
  if (!body.descripcion) return toast('La descripción es obligatoria.');
  if (isNaN(body.latitud) || isNaN(body.longitud)) return toast('Ingresa coordenadas válidas.');
  try {
    const r = await postJSON('api/admin/puntos_seguridad.php', body);
    modal.hide();
    toast(r.mensaje || 'Guardado');
    cargarPuntos();
  } catch (e) { toast(e.message); }
}

async function togglePunto(id) {
  const p = puntosCache.find(x => x.id === id);
  if (!p) return;
  try {
    await postJSON('api/admin/puntos_seguridad.php', {
      accion: 'guardar', id: p.id, tipo: p.tipo, fuente: p.fuente,
      descripcion: p.descripcion, latitud: parseFloat(p.latitud),
      longitud: parseFloat(p.longitud), activo: p.activo == 1 ? 0 : 1,
    });
    toast(p.activo == 1 ? 'Punto desactivado' : 'Punto activado');
    cargarPuntos();
  } catch (e) { toast(e.message); }
}

// ═══════════════ Usuarios ═══════════════
let usuariosCache = [];
const usuarioActual = { id: <?= (int)$usuario['id'] ?> };

async function cargarUsuarios() {
  const cont = $('#seccion-usuarios');
  cont.innerHTML = `
    <div class="d-flex align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="icon-users me-2"></i>Usuarios</h5></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
      <thead class="table-light small"><tr>
        <th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Activo</th><th>Registro</th><th class="text-end">Acciones</th>
      </tr></thead><tbody id="usuariosBody"></tbody></table></div></div>`;
  try {
    usuariosCache = await api('api/admin/usuarios.php');
    renderUsuarios();
  } catch (e) { renderUsuariosError(cont, e); }
}

function renderUsuariosError(cont, e) {
  cont.innerHTML += '<div class="alert alert-danger small mt-3">' + esc(e.message) + '</div>';
}

function renderUsuarios() {
  const tbody = $('#usuariosBody');
  tbody.innerHTML = usuariosCache.map(u => {
    const esYo = u.id === usuarioActual.id;
    return `
    <tr class="${u.activo == 0 ? 'table-secondary opacity-75' : ''}">
      <td class="small">${u.id}</td>
      <td class="small fw-semibold">${esc(u.nombre)} ${esYo ? '<span class="badge bg-info text-dark">tú</span>' : ''}</td>
      <td class="small">${esc(u.correo)}</td>
      <td>
        <select class="form-select form-select-sm" style="width:130px;" ${esYo ? 'disabled' : ''} onchange="cambiarRol(${u.id}, this.value)">
          <option value="usuario" ${u.rol === 'usuario' ? 'selected' : ''}>Usuario</option>
          <option value="admin" ${u.rol === 'admin' ? 'selected' : ''}>Admin</option>
        </select>
      </td>
      <td>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" ${u.activo == 1 ? 'checked' : ''} ${esYo ? 'disabled' : ''} onchange="cambiarActivo(${u.id}, this.checked)">
        </div>
      </td>
      <td class="small text-muted">${esc(u.fecha_registro)}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-danger" ${esYo ? 'disabled' : ''} onclick="eliminarUsuario(${u.id})"><i class="icon-trash-2"></i></button>
      </td>
    </tr>`;
  }).join('');
}

async function cambiarRol(id, rol) {
  try {
    await postJSON('api/admin/usuarios.php', { accion: 'guardar', id, rol, activo: usuariosCache.find(u => u.id === id).activo });
    toast('Rol actualizado');
    cargarUsuarios();
  } catch (e) { toast(e.message); cargarUsuarios(); }
}

async function cambiarActivo(id, activo) {
  try {
    await postJSON('api/admin/usuarios.php', { accion: 'guardar', id, rol: usuariosCache.find(u => u.id === id).rol, activo: activo ? 1 : 0 });
    toast(activo ? 'Usuario activado' : 'Usuario desactivado');
    cargarUsuarios();
  } catch (e) { toast(e.message); cargarUsuarios(); }
}

async function eliminarUsuario(id) {
  const u = usuariosCache.find(x => x.id === id);
  if (!confirm('¿Eliminar definitivamente a ' + (u ? u.nombre : 'este usuario') + '?')) return;
  try {
    const r = await postJSON('api/admin/usuarios.php', { accion: 'eliminar', id });
    toast(r.mensaje || 'Eliminado');
    cargarUsuarios();
  } catch (e) { toast(e.message); }
}

// ═══════════════ Reportes ═══════════════
async function cargarReportes() {
  const cont = $('#seccion-reportes');
  cont.innerHTML = `
    <div class="d-flex align-items-center mb-3"><h5 class="fw-bold mb-0"><i class="icon-message-square-text me-2"></i>Reportes de usuarios</h5></div>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
      <thead class="table-light small"><tr>
        <th>ID</th><th>Fecha</th><th>Usuario</th><th>Punto</th><th>Comentario</th><th class="text-end">Acciones</th>
      </tr></thead><tbody id="reportesBody"></tbody></table></div></div>`;
  try {
    const datos = await api('api/admin/reportes.php');
    const tbody = $('#reportesBody');
    tbody.innerHTML = datos.length ? datos.map(r => `
      <tr>
        <td class="small">${r.id}</td>
        <td class="small">${esc(r.fecha)}</td>
        <td class="small">${esc(r.usuario_nombre)}<br><span class="text-muted">${esc(r.usuario_correo)}</span></td>
        <td>${badgeTipo(r.punto_tipo)}</td>
        <td class="small">${esc(r.comentario)}</td>
        <td class="text-end"><button class="btn btn-sm btn-outline-danger" onclick="eliminarReporte(${r.id})"><i class="icon-trash-2"></i></button></td>
      </tr>`).join('') : '<tr><td colspan="6" class="text-center text-muted small py-4">Sin reportes</td></tr>';
  } catch (e) {
    cont.innerHTML += '<div class="alert alert-danger small mt-3">' + esc(e.message) + '</div>';
  }
}

async function eliminarReporte(id) {
  if (!confirm('¿Eliminar este reporte?')) return;
  try {
    const r = await postJSON('api/admin/reportes.php', { accion: 'eliminar', id });
    toast(r.mensaje || 'Eliminado');
    cargarReportes();
  } catch (e) { toast(e.message); }
}

// ═══════════════ Botón guardar del modal ═══════════════
$('#btnGuardarModal').addEventListener('click', async () => {
  const seccion = document.querySelector('#sidebarNav .nav-link.active').dataset.seccion;
  if (seccion === 'paradas') await guardarParada();
  if (seccion === 'rutas') await guardarRuta();
  if (seccion === 'puntos') await guardarPunto();
});

// ═══════════════ Logout ═══════════════
$('#btnLogout').addEventListener('click', async () => {
  try { await fetch('api/auth/logout.php', { method: 'POST' }); } catch (_) {}
  window.location.href = 'login.php';
});

// ═══════════════ Inicio ═══════════════
cargarResumen();
</script>
</body>
</html>
