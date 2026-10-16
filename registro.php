<?php
require_once __DIR__ . '/config/auth.php';

$usuario = usuarioLogueado();
if ($usuario !== null) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro — MyGTS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #01044A, #020674);
      padding: 16px;
    }
    .auth-card {
      width: 100%;
      max-width: 420px;
      border: 0;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.50);
    }
  </style>
</head>
<body>
  <div class="card auth-card">
    <div class="card-body p-4 p-sm-5">
      <div class="text-center mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle text-white" style="width:64px;height:64px;background:linear-gradient(135deg,#01044A,#020674);">
          <i class="icon-user-plus fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1">Crear cuenta</h4>
        <p class="text-muted small mb-0">Únete a MyGTS y guarda tus rutas seguras</p>
      </div>

      <div id="alertError" class="alert alert-danger small py-2 d-none" role="alert"></div>

      <form id="registroForm" novalidate>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Nombre completo</label>
          <input type="text" class="form-control form-control-lg rounded-pill" id="nombre" placeholder="Ej. Ana Martínez" required autocomplete="name">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Correo electrónico</label>
          <input type="email" class="form-control form-control-lg rounded-pill" id="correo" placeholder="tucorreo@ejemplo.com" required autocomplete="email">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Contraseña</label>
          <input type="password" class="form-control form-control-lg rounded-pill" id="password" placeholder="Mínimo 6 caracteres" required minlength="6" autocomplete="new-password">
        </div>
        <div class="mb-4">
          <label class="form-label small fw-semibold text-muted">Confirmar contraseña</label>
          <input type="password" class="form-control form-control-lg rounded-pill" id="password2" placeholder="Repite tu contraseña" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2" id="btnRegistro">
          <i class="icon-user-check me-1"></i>Crear cuenta
        </button>
      </form>

      <div class="text-center mt-3 small">
        <span class="text-muted">¿Ya tienes cuenta?</span>
        <a href="login.php" class="text-decoration-none fw-semibold">Inicia sesión</a>
      </div>
      <div class="text-center mt-2 small">
        <a href="index.php" class="text-decoration-none text-muted"><i class="icon-arrow-left me-1"></i>Volver al mapa</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const form = document.getElementById('registroForm');
    const btn = document.getElementById('btnRegistro');
    const alertError = document.getElementById('alertError');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      alertError.classList.add('d-none');

      const nombre = document.getElementById('nombre').value.trim();
      const correo = document.getElementById('correo').value.trim();
      const password = document.getElementById('password').value;
      const password2 = document.getElementById('password2').value;

      if (!nombre || !correo || !password) return;
      if (password !== password2) {
        alertError.textContent = 'Las contraseñas no coinciden.';
        alertError.classList.remove('d-none');
        return;
      }
      if (password.length < 6) {
        alertError.textContent = 'La contraseña debe tener al menos 6 caracteres.';
        alertError.classList.remove('d-none');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creando cuenta...';

      try {
        const res = await fetch('api/auth/registro.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nombre, correo, password }),
        });
        const data = await res.json();

        if (data.status !== 'ok') {
          alertError.textContent = data.mensaje || 'Error al crear la cuenta.';
          alertError.classList.remove('d-none');
          btn.disabled = false;
          btn.innerHTML = '<i class="icon-user-check me-1"></i>Crear cuenta';
          return;
        }

        window.location.href = 'index.php';
      } catch (err) {
        console.warn('Registro error:', err);
        alertError.textContent = 'Error de conexión con el servidor.';
        alertError.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="icon-user-check me-1"></i>Crear cuenta';
      }
    });
  </script>
</body>
</html>
