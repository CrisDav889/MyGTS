# MyGTS — Documentación del Proyecto

## 1. Objetivo

Construir una aplicación web tipo GPS que, en lugar de priorizar la ruta más rápida, prioriza la ruta más segura según el modo de transporte elegido (a pie, en auto, en transporte público), marcando en el mapa paradas de transporte público y puntos relativamente seguros/inseguros, con una capa de inteligencia artificial que enriquezca la recomendación.


MyGTS es un sistema web de planificación de rutas de transporte urbano para la Ciudad de Guatemala. Su objetivo principal es **calcular rutas multi-modales óptimas** combinando transporte público (buses, Transmetro) y tramos a pie, teniendo en cuenta **niveles de seguridad ciudadana** basados en datos colaborativos.

El sistema permite al usuario:
- Buscar paradas de transporte público por nombre o dirección
- Seleccionar origen y destino mediante un mapa interactivo o buscador con autocompletado
- Calcular la mejor ruta multi-modal minimizando tiempo de viaje y evitando zonas inseguras
- Visualizar los tramos de la ruta sobre el mapa, con trazos sobre calles reales (OSRM) para tramos de transporte
- Consultar paradas, rutas y puntos de seguridad disponibles

---

## 2. Arquitectura del Sistema

### 2.1. Vista General

```
┌─────────────────────────────────────────────────────────────────┐
│                        NAVEGADOR WEB                            │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    index.php (HTML)                       │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐  │  │
│  │  │ Leaflet  │  │ Bootstrap│  │ main.js  │  │ style   │  │  │
│  │  │  Map     │  │    UI    │  │ (lógica) │  │  .css   │  │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └─────────┘  │  │
│  └───────────────────────────────────────────────────────────┘  │
│                             │ HTTP                               │
└─────────────────────────────┼───────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     SERVIDOR APACHE / PHP 8+                     │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  config/                  │  api/                         │  │
│  │  ├─ env.php (parse .env)  │  ├─ paradas.php              │  │
│  │  ├─ database.php (PDO)    │  ├─ rutas.php                │  │
│  │  ├─ init.php (JSON utils) │  ├─ horarios.php             │  │
│  │  └─ auth.php (sesión)     │  ├─ buscar_ruta.php          │  │
│  │                           │  ├─ puntos_seguridad.php     │  │
│  │                           │  ├─ reportes.php             │  │
│  │                           │  ├─ asistente.php (IA)       │  │
│  │                           │  ├─ mis_rutas.php            │  │
│  │                           │  └─ ruta_segura.php          │  │
│  │                           │  ├─ auth/ (login, registro)  │  │
│  │                           │  └─ admin/ (panel)           │  │
│  └───────────────────────────────────────────────────────────┘  │
│                             │ PDO                                │
└─────────────────────────────┼───────────────────────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   MySQL 8+ / MariaDB 10.5+                       │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Base de datos: ExpoCarrera                              │  │
│  │  ├─ usuarios                  │  ├─ rutas                 │  │
│  │  ├─ paradas                   │  ├─ rutas_paradas         │  │
│  │  ├─ horarios                  │  ├─ puntos_seguridad      │  │
│  │  ├─ reportes                  │  ├─ rutas_guardadas       │  │
│  │  └─ ubicaciones_frecuentes    │                           │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2. Frontend (CLIENTE)

| Componente | Tecnología | Propósito |
|---|---|---|
| Mapa | Leaflet.js + OpenStreetMap tiles | Visualización geográfica interactiva |
| UI | Bootstrap 5.3.3 + iconos Lucide | Interfaz responsive, menús, formularios |
| Lógica | `js/main.js` (vanilla JS ES2020+) | Toda la interacción del lado cliente |
| CSS | `css/style.css` | Personalización visual (paleta Emerald & Mint) |

**Estructura de `main.js`:**
1. Inicialización del mapa y capas de datos (paradasLayer, seguridadLayer, rutasLayer, routeResultLayer)
2. Carga asíncrona de paradas (`cargarParadas()`), rutas (`cargarRutas()`), puntos de seguridad (`cargarSeguridad()`)
3. Geocalización GPS (marcador de usuario con animación ping)
4. Autocompletado para el buscador superior (topbar) y los campos de origen/destino del panel de ruteo
5. Motor de ruteo en frontend: envía petición a `api/ruta_segura.php`, dibuja tramos en el mapa, consulta OSRM para tramos de transporte (caminatas = líneas rectas)
6. Modo selección en el mapa (pick de origen/destino)
7. Chat IA Gity flotante (`api/asistente.php`) con dibujo de rutas sugeridas en el mapa
8. Controles flotantes (brújula, zoom, barra de escala con posicionamiento dinámico)

### 2.3. Backend (SERVIDOR)

| Archivo | Propósito |
|---|---|
| `config/env.php` | Parsea el archivo `.env` y expone variables de entorno |
| `config/database.php` | Conexión PDO a MySQL (clase `Database`) |
| `config/init.php` | Headers JSON + CORS + helpers `jsonOutput()` / `jsonError()` |
| `config/auth.php` | Sesiones (`MYGTS_SESION`), login/logout, roles y `requiereAdmin()` |
| `api/paradas.php` | CRUD de paradas (GET con filtro `?q=` y paginación `has_more`) |
| `api/rutas.php` | CRUD de rutas (listado y detalle con paradas ordenadas) |
| `api/horarios.php` | Horarios por ruta (`?ruta_id=`) con ordenamiento portable |
| `api/buscar_ruta.php` | Búsqueda de rutas por origen/destino/transporte con filtros |
| `api/puntos_seguridad.php` | Puntos de seguridad (`?tipo=seguro|moderado|inseguro`) |
| `api/reportes.php` | **Reporte colaborativo** (POST requiere sesión; crea punto de seguridad `fuente='reporte'` + registro en `reportes`) |
| `api/ruta_segura.php` | **Motor de ruteo principal** (ver sección 4) |
| `api/asistente.php` | Chat IA Gity (OpenRouter) con contexto de rutas/paradas |
| `api/mis_rutas.php` | Rutas guardadas del usuario (CRUD + favoritos, requiere sesión) |
| `api/auth/login.php` | Inicio de sesión (verifica `password_hash`) |
| `api/auth/registro.php` | Registro de usuarios |
| `api/auth/logout.php` | Cierre de sesión |
| `api/auth/sesion.php` | Estado de sesión actual |
| `api/admin/*.php` | Panel de administración (paradas, rutas, puntos, usuarios, reportes, resumen) |

### 2.4. Base de Datos (MySQL)

**Base:** `ExpoCarrera` (charset utf8mb4, collation utf8mb4_unicode_ci)

**Tablas:**

| Tabla | Función |
|---|---|
| `usuarios` | Cuentas de usuario registradas |
| `rutas` | Líneas de transporte (nombre, tipo: Bus/Transmetro, color mapa) |
| `paradas` | Paradas con coordenadas, código único |
| `rutas_paradas` | Relación N:M con orden y tiempo desde inicio |
| `horarios` | Frecuencias por ruta y día de la semana |
| `puntos_seguridad` | Zonas clasificadas como seguro/moderado/inseguro |
| `reportes` | Reportes de usuarios sobre puntos de seguridad |
| `rutas_guardadas` | Rutas favoritas por usuario |
| `ubicaciones_frecuentes` | Ubicaciones guardadas por usuario |

---

## 3. Dependencias

### 3.1. Servidor

| Requisito | Versión mínima | Notas |
|---|---|---|
| PHP | 8.0+ | Usa `match()`, `str_starts_with()`, typed properties |
| Extensiones PHP | PDO, PDO_MySQL | `pdo_mysql` para conexión a base de datos |
| MySQL | 8.0+ / MariaDB 10.5+ | Funciones GIS opcionales (`ST_Distance_Sphere`) |
| Apache | 2.4+ | Con `mod_rewrite` y `AllowOverride` |
| HTTPS | Recomendado | Geolocalización GPS requiere contexto seguro |

### 3.2. Frontend (CDN)

| Recurso | CDN | Versión |
|---|---|---|
| Bootstrap CSS | `cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css` | 5.3.3 |
| Iconos | `cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css` | última estable |
| Bootstrap JS | `cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js` | 5.3.3 |
| Leaflet CSS | `unpkg.com/leaflet/dist/leaflet.css` | última estable |
| Leaflet JS | `unpkg.com/leaflet/dist/leaflet.js` | última estable |
| OpenStreetMap Tiles | `tile.openstreetmap.org/{z}/{x}/{y}.png` | — |
| OSRM API | `router.project-osrm.org` (público, ~1000 req/día) | driving profile (solo transporte, caminatas = línea recta) |

### 3.3. Configuración

Archivo `.env` en la raíz del proyecto (ver `.env.example`):
```
DB_HOST=127.0.0.1
DB_NAME=ExpoCarrera
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
OPENROUTER_API_KEY=sk-or-v1-TU_CLAVE_AQUI
```

---

## 4. Funcionamiento Detallado

### 4.1. Flujo de Ruteo (endpoint crítico)

`GET /api/ruta_segura.php` recibe:
- `origen_lat`, `origen_lng`, `origen_text`
- `destino_lat`, `destino_lng`, `destino_text`
- `transporte` (todos|Bus|Transmetro)
- `evitar_inseguro` (1|0)

**Algoritmo de Ruteo (en dos fases):**

```
FASE 1 — Búsqueda de línea directa (findDirectTransitPairs):
  · Antes de cualquier búsqueda multimodal, consulta en la base de datos si existe
    alguna línea de transporte que cubra DIRECTAMENTE el área de origen y de destino
    en el sentido correcto (orden_origen < orden_destino).
  · Si existen opciones directas, se evalúan con Dijkstra y se aplica un descuento
    preferencial de -10 min en su peso, priorizándolas sobre rutas con transbordos.
  · El resultado incluye la estación más cercana al origen y al destino de esa línea.

FASE 2 — Búsqueda multimodal (fallback si no hay línea directa):
  · findNearestStops() para origen y destino (radio 1.0 km → expansión hasta 5.0 km, máx 4 paradas).
  · Se ejecuta Dijkstra sobre el grafo completo evaluando hasta 4×4 combinaciones.

buildTransitGraph() — Construcción del grafo:
  a. Aristas de transporte: segmentos ordenados de rutas_paradas (Bus / Transmetro)
     - Peso = tiempo de viaje + penalización por seguridad
     - Penalización basada en puntos_seguridad cercanos (radio 500m):
       * inseguro: +15 min · moderado (>2): +8 min · moderado (1-2): +3 min
  b. Filtro de aristas peatonales (regla de continuidad a bordo):
     - Se construye el mapa `transitConnections` con todos los pares (A,B) ya
       conectados por una arista de tránsito en cualquier dirección.
     - Una arista peatonal A→B solo se crea si ese par NO existe en transitConnections.
     - Esto garantiza que el pasajero NUNCA baje a caminar entre dos paradas que ya
       están conectadas por la misma línea en servicio (ej. Rodolfo Robles → San Juan).
     - Las caminatas solo sirven para transbordos reales entre líneas distintas.

Dijkstra — Pesos y penalizaciones:
  · Continuidad en la misma línea: lastTransitRoute rastrea la última línea usada.
  · Desabordaje modal (+3 min): costo por pasar de tránsito a caminata.
  · Reembarque en la misma línea (+30 min): penalización disuasoria para evitar
    bajarse y volver a subir en el mismo bus/transmetro.
  · Transbordo real (+5 min): aplica solo al cambiar entre dos líneas distintas.

buildRouteResponse() — Respuesta estructurada:
  a. Tramo peatonal inicial (Origen → parada de abordaje)
  b. Tramos de tránsito continuos por línea (ininterrumpidos a bordo)
  c. Marcadores de transbordo solo si cambia de línea
  d. Tramo peatonal final (parada de desembarque → Destino)
  e. Campos adicionales en el JSON:
     - `es_linea_directa`: true si todo el tránsito ocurre en una sola línea
     - `linea_directa_nombre`: nombre de esa línea (ej. "Transmetro L12")
     - `tiempo_viaje`, `tiempo_transbordos`, `penalidad_total`, `tiempo_total`
```

### 4.2. OSRM (Caminos sobre calles reales)

En el frontend (`js/main.js`), al mostrar el resultado de ruta:

```
displayRouteResult():
  1. Consultar OSRM solo para tramos de transporte (Bus/Transmetro) con ≥2 paradas:
     a. Tramos de Bus/Transmetro → perfil `driving` (calles reales)
     b. Tramos a pie (`caminando`) → NO se consulta OSRM, se genera línea recta entre origen y destino del tramo
     c. Se pasan TODAS las paradas del tramo como waypoints (solo transporte)
     d. Transbordos se excluyen (trazado recto)
  2. Cachear resultado en sessionStorage (`osrm_driving_{waypoints}`), almacena `{coords, distance, duration}`
  3. Si OSRM falla o no responde (timeout 5s) → caída a línea recta entre puntos
  4. Dibujar en mapa con estilo diferenciado y limpio:
     - Walking (a pie): trazado discontinuo (#666, weight 3, dashArray '10, 10')
     - Transbordo: trazado discontinuo (#f9a825, weight 4, dashArray '3, 6')
     - Transporte: línea continua según nivel de seguridad (Verde / Amarillo / Rojo)
     - Marcadores únicamente en: abordaje (verde), transbordo (amarillo) y destino (rojo)
  5. Mostrar distancia OSRM (km) y duración de cada tramo de transporte en el resumen
  6. Calcular y presentar la distancia total acumulada de la ruta (km), con OSRM antes del resumen

Badge de línea directa (frontend):
  · Si `ruta.es_linea_directa === true`, se muestra una insignia verde con ⚡ en el
    resumen: "Línea Directa (nombre de la línea)" sin transbordos.
  · Si hay transbordos, solo se muestra el badge de seguridad normal.
```

### 4.3. Búsqueda Direccional de Rutas

`GET /api/buscar_ruta.php?destino=<texto>&origen=<texto>&transporte=<tipo>`:

1. Busca paradas que coincidan con la búsqueda de destino.
2. Si se proporciona origen, valida direccionalidad (`rp_orig.orden < rp.orden`), garantizando que solo se retornen rutas donde la parada de origen antecede a la de destino.
3. Incluye puntos de seguridad relevantes para el trayecto.

### 4.4. Capas del Mapa

| Capa | Descripción | Ícono |
|---|---|---|
| `paradasLayer` | Paradas de transporte | Bus icon (24px azul) |
| `seguridadLayer` | Puntos de seguridad | Shield verde (30px) / Shield amarillo (30px) / Triángulo rojo (30px) |
| `rutasLayer` | Polilíneas de rutas predefinidas | weight 2, opacity 0.5 |
| `routeResultLayer` | Resultado del ruteo actual (con OSRM) | weight 4, opacity 0.8 |

### 4.5. Reporte Colaborativo de Seguridad (endpoint disponible, sin UI)

`POST /api/reportes.php` (requiere sesión iniciada) — el botón de reporte fue retirado de la interfaz, pero el endpoint permanece disponible para integraciones futuras:

1. El cliente envía tipo (seguro/moderado/inseguro), ubicación y comentario.
2. El endpoint valida coordenadas, tipo y comentario (máx. 500 caracteres).
3. Si ya existe un punto de seguridad activo a menos de ~50 m, se reutiliza su `id`; en caso contrario se crea uno nuevo con `fuente='reporte'` y `activo=1`.
4. En una transacción se inserta el registro en `reportes` (usuario + punto + comentario).
5. Los administradores revisan y gestionan los reportes desde el panel (`api/admin/reportes.php`).

---

## 5. Estructura de Archivos

```
MyGTS/
├── .env                     # Credenciales de BD + clave OpenRouter
├── .env.example             # Plantilla para .env
├── index.php                # Página principal (mapa + menús + chat IA)
├── login.php                # Página de inicio de sesión
├── registro.php             # Página de registro
├── logout.php               # Cierre de sesión (página)
├── admin.php                # Panel de administración
├── DOCUMENTACION.md         # Documentación oficial del proyecto
│
├── config/
│   ├── env.php              # Parser de .env
│   ├── database.php         # Conexión PDO (clase Database)
│   ├── init.php             # Headers + helpers JSON
│   └── auth.php             # Sesiones, roles y requiereAdmin()
│
├── api/
│   ├── paradas.php          # Endpoint de paradas
│   ├── rutas.php            # Endpoint de rutas
│   ├── horarios.php         # Endpoint de horarios
│   ├── buscar_ruta.php      # Búsqueda direccional de rutas
│   ├── puntos_seguridad.php # Endpoint de puntos de seguridad
│   ├── reportes.php         # Reporte colaborativo (requiere sesión)
│   ├── asistente.php        # Chat IA Gity (OpenRouter)
│   ├── mis_rutas.php        # Rutas guardadas del usuario
│   ├── ruta_segura.php      # Motor de ruteo (Dijkstra multimodal + zonas seguras)
│   ├── auth/                # login.php, registro.php, logout.php, sesion.php
│   └── admin/               # paradas.php, rutas.php, puntos_seguridad.php,
│                            # usuarios.php, reportes.php, resumen.php
│
├── js/
│   └── main.js              # Lógica frontend completa (Leaflet + OSRM + IA + reportes)
│
├── css/
│   └── style.css            # Estilos personalizados (Emerald & Mint)
│
├── database/
│   ├── esquema_completo.sql # Esquema + datos de ejemplo
│   ├── migracion_mas_rutas.sql  # Migración de rutas/paradas adicionales
│   └── paradas_limpias.csv  # Datos de paradas limpios
│
└── backups/                 # Archivos de respaldo
```

---

## 6. Constantes y Parámetros Clave

| Constante | Archivo | Valor | Descripción |
|---|---|---|---|
| `TRANSFER_PENALTY` | `ruta_segura.php` | 5 min | Penalización por transbordo de línea |
| `WALKING_SPEED_KMH` | `ruta_segura.php` | 4.5 km/h | Velocidad promedio peatonal |
| `BUS_SPEED_KMH` | `ruta_segura.php` | 22 km/h | Velocidad promedio bus urbano |
| `TRANSMETRO_SPEED_KMH` | `ruta_segura.php` | 28 km/h | Velocidad promedio Transmetro |
| `INITIAL_SEARCH_RADIUS_KM` | `ruta_segura.php` | 1.0 km | Radio inicial para paradas cercanas |
| `MAX_SEARCH_RADIUS_KM` | `ruta_segura.php` | 5.0 km | Radio máximo con expansión progresiva |
| `MAX_NEAREST_STOPS` | `ruta_segura.php` | 4 | Máx. paradas candidatas por extremo |
| `WALKING_CONNECT_RADIUS_KM` | `ruta_segura.php` | 0.8 km | Radio máximo para conexiones a pie entre paradas |
| `WALKING_ROUTE_ID` | `ruta_segura.php` | -1 | ID interno para tramos a pie |
| `DIRECT_LINE_BONUS` | `ruta_segura.php` | -10 min | Descuento preferencial aplicado a rutas de línea directa en Dijkstra |
| `MODAL_CHANGE_PENALTY` | `ruta_segura.php` | +3 min | Costo por bajarse del vehículo para caminar |
| `REBOARD_SAME_LINE_PENALTY` | `ruta_segura.php` | +30 min | Penalización por caminar y reembarcarse en la misma línea |
| `OSRM_TIMEOUT` | `main.js` | 5000 ms | Timeout por solicitud a OSRM |
| Perfil OSRM | `main.js` | `driving` | Trazado real solo para transporte público (caminatas = línea recta) |

---

## 7. Datos de Ejemplo

El archivo `database/esquema_completo.sql` incluye:

- **3 usuarios** de prueba (Juan, María, Carlos)
- **21 rutas** de transporte (buses urbanos, Transmetro y líneas Transurbano)
- **156 paradas** en zonas clave de la Ciudad de Guatemala
- **183 relaciones** ruta–parada (con orden y tiempo desde inicio)
- **Horarios** por tipo de día (laborables, fines de semana)
- **Puntos de seguridad** (seguros, moderados, inseguros) en zonas clave

---

## 8. Notas Técnicas

- **CORS**: Todos los endpoints PHP incluyen `Access-Control-Allow-Origin: *`
- **Errores**: Respuestas en formato JSON consistente (`jsonOutput()`). Los mensajes de error internos se registran con `error_log()` y al cliente solo se le envía un mensaje genérico.
- **Caché OSRM**: Almacenamiento en `sessionStorage` para optimizar peticiones a la API de OSRM
- **Portabilidad SQL**: Consultas optimizadas compatibles con MySQL 8+ y MariaDB 10.5+
- **PHP 8+**: Requiere PHP 8.0+ para características de tipado y expresiones `match()`
- **Iconos**: Se usa la fuente `lucide-static` (no Bootstrap Icons)
- **Reportes**: `api/reportes.php` exige sesión (`config/auth.php`) y usa transacciones para crear el punto de seguridad y el reporte de forma atómica. El botón de la interfaz fue retirado; el endpoint queda disponible sin UI.
- **Regla de continuidad a bordo**: El mapa `transitConnections` se construye después de insertar todas las aristas de tránsito. Ningún par `(A, B)` que ya esté conectado por una línea de bus o Transmetro recibe también una arista peatonal. Esto elimina el caso en que Dijkstra elegiría "caminar" entre paradas consecutivas de la misma línea.
- **Prioridad de línea directa**: `findDirectTransitPairs()` realiza una consulta SQL cruzada entre el área de origen y de destino buscando pares de paradas unidas por la misma `ruta_id` con `orden_inicio < orden_fin`. Los pares hallados se evalúan primero con un bono de -10 en su peso, asegurando preferencia sobre rutas con transbordos.
- **Metadatos de respuesta**: El campo `es_linea_directa` (bool) y `linea_directa_nombre` (string|null) están disponibles en el JSON de respuesta de `api/ruta_segura.php` para que el frontend pueda mostrar el indicador visual de línea directa.
