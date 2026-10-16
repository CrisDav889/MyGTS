<?php
require_once __DIR__ . '/config/auth.php';

cerrarSesion();

header('Location: index.php');
exit;
