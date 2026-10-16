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
  <title>Iniciar sesión — MyGTS</title>
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
      max-width: 400px;
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
          <i class="icon-map fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1">Bienvenido a MyGTS</h4>
        <p class="text-muted small mb-0">Inicia sesión para guardar tus rutas</p>
      </div>

      <div id="alertError" class="alert alert-danger small py-2 d-none" role="alert"></div>

      <form id="loginForm" novalidate>
        <div class="mb-3">
          <label class="form-label small fw-semibold text-muted">Correo electrónico</label>
          <input type="email" class="form-control form-control-lg rounded-pill" id="correo" placeholder="tucorreo@ejemplo.com" required autocomplete="email">
        </div>
        <div class="mb-4">
          <label class="form-label small fw-semibold text-muted">Contraseña</label>
          <input type="password" class="form-control form-control-lg rounded-pill" id="password" placeholder="••••••••" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2" id="btnLogin">
          <i class="icon-log-in me-1"></i>Iniciar sesión
        </button>
      </form>

      <div class="text-center mt-3 small">
        <span class="text-muted">¿No tienes cuenta?</span>
        <a href="registro.php" class="text-decoration-none fw-semibold">Regístrate</a>
      </div>
      <div class="text-center mt-2 small">
        <a href="index.php" class="text-decoration-none text-muted"><i class="icon-arrow-left me-1"></i>Volver al mapa</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('btnLogin');
    const alertError = document.getElementById('alertError');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      alertError.classList.add('d-none');

      const correo = document.getElementById('correo').value.trim();
      const password = document.getElementById('password').value;
      if (!correo || !password) return;

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Ingresando...';

      try {
        const res = await fetch('api/auth/login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ correo, password }),
        });
        const data = await res.json();

        if (data.status !== 'ok') {
          alertError.textContent = data.mensaje || 'Error al iniciar sesión.';
          alertError.classList.remove('d-none');
          btn.disabled = false;
          btn.innerHTML = '<i class="icon-log-in me-1"></i>Iniciar sesión';
          return;
        }

        window.location.href = data.usuario && data.usuario.rol === 'admin' ? 'admin.php' : 'index.php';
      } catch (err) {
        console.warn('Login error:', err);
        alertError.textContent = 'Error de conexión con el servidor.';
        alertError.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="icon-log-in me-1"></i>Iniciar sesión';
      }
    });
  </script>
</body>
</html>
