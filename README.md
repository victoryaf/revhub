# RevHub — Comunidad del Motor & Quedadas

Plataforma web para la gestión de quedadas y eventos del mundo del motor. Permite a los usuarios registrarse, añadir sus vehículos, consultar eventos e inscribirse en aquellos que les interesen. Los organizadores pueden crear y gestionar sus propios eventos, y los administradores tienen control total sobre la plataforma.

---

## Tecnologías

- **PHP 8** — lógica del servidor y gestión de sesiones
- **MySQL** — base de datos relacional
- **HTML5 + CSS3** — estructura y diseño de las vistas
- **JavaScript (ES6+)** — interactividad, modales y llamadas AJAX
- **Leaflet.js + OSRM** — mapas interactivos y cálculo de rutas
- **Font Awesome 6.5** — iconografía
- **PHPMailer** — envío de emails para recuperación de contraseña

---

## Requisitos

- XAMPP v8.x (Apache + MySQL + PHP 8.0+)
- Navegador web moderno (Chrome, Firefox, Edge o Safari)
- Git

---

## Instalación

1. Clona el repositorio en la carpeta `htdocs` de XAMPP:

```bash
cd C:\xampp\htdocs
git clone https://github.com/usuario/revhub.git
```

2. Inicia Apache y MySQL desde el panel de control de XAMPP.

3. Accede a **phpMyAdmin** en `http://localhost/phpmyadmin` y crea una base de datos llamada `revhub` con cotejamiento `utf8mb4_unicode_ci`.

4. Importa el archivo `revhub.sql` desde la pestaña **Importar** de phpMyAdmin.

5. Comprueba que `php/conexion.php` tiene los datos correctos:

```php
$host     = 'localhost';
$usuario  = 'root';
$password = '';
$base     = 'revhub';
```

6. Accede a la aplicación en `http://localhost/revhub`.

---

## Credenciales por defecto

| Campo | Valor |
|-------|-------|
| Email | admin@revhub.com |
| Contraseña | admin123 |

> Se recomienda cambiar la contraseña tras la primera instalación desde la sección **Perfil**.

---

## Estructura del proyecto

```
revhub/
├── index.php               Página de inicio
├── eventos.php             Listado de eventos
├── evento.php              Detalle de evento
├── perfil.php              Perfil de usuario
├── vehiculos.php           Gestión de vehículos
├── crear_evento.php        Crear evento (organizador)
├── editar_evento.php       Editar evento (organizador)
├── admin.php               Panel de administración
├── registro.php            Registro de usuarios
├── recuperar.php           Recuperación de contraseña
├── logout.php              Cerrar sesión
├── cookies.php             Política de cookies
├── php/
│   ├── conexion.php        Conexión a MySQL
│   └── get_vehiculos.php   Endpoint AJAX vehículos
├── css/
│   └── style.css           Hoja de estilos principal
├── img/
│   ├── perfiles/           Fotos de perfil
│   ├── vehiculos/          Imágenes de vehículos
│   └── eventos/            Carteles de eventos
├── includes/
│   ├── cabecera.php        Cabecera común
│   └── pie.php             Pie de página común
└── revhub.sql              Esquema de la base de datos
```

---

## Funcionalidades principales

- Registro e inicio de sesión con recuperación de contraseña por email
- Gestión de perfil y vehículos por parte del usuario
- Listado de eventos con filtros por tipo y buscador
- Eventos de tipo ruta con mapa interactivo y cálculo de distancia
- Inscripción en eventos con selección de vehículo y validación de requisitos
- Sistema de comentarios y mensajería privada entre usuarios
- Panel de administración con gestión de usuarios, eventos y contenido

---

## Autor

Victoria Ausín Fernández — Desarrollo de Aplicaciones Web (DAW) — Curso 2025-2026
