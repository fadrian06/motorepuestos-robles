# Reglas para Vistas Twig y Diseño UI

Las plantillas del sistema se ubican en `resources/views/pages/` y deben preservar estrictamente el sistema de diseño visual corporativo.

## 1. Sistema de Diseño y Estilos

- Hoja de estilos base: `resources/styles/w3.css` con variables CSS propias de la marca:
  - `--brand-red: #dc2626;` (color principal)
  - `--brand-red-hover: #b91c1c;`
  - `--brand-red-glow: rgba(220, 38, 38, 0.25);`
  - `--brand-black: #0d0d11;`
  - `--brand-charcoal: #18181b;`
  - `--sidebar-width: 260px;`
- Paleta semántica W3: `--w3-surface`, `--w3-surface-alt`, `--w3-border-light`, `--w3-text-color`.
- Soporte para Dark Mode controlado mediante Alpine.js en la etiqueta `<body>`:
  ```html
  <body
    x-data="{
      isDarkMode: window.matchMedia('(prefers-color-scheme: dark)').matches,
      sidebarOpen: false,
      init() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this.isDarkMode = mediaQuery.matches;
        mediaQuery.addEventListener('change', (e) => { this.isDarkMode = e.matches; });
      }
    }"
    :class="{ 'w3-dark-mode': isDarkMode }"
  >
  ```

## 2. Layout y Estructura de Páginas

Toda vista del panel administrativo se compone de:
1. **Sidebar Persistente (`.app-sidebar`):**
   - Cabecera con avatar/banner de la empresa y estado "Sistema en línea".
   - Grupos de navegación:
     - **Principal:** Dashboard (`/`).
     - **Gestión de Negocio:** Clientes (`/clientes`), Proveedores (`/proveedores`), Vendedores (`/vendedores`), Tasas de Cambio (`/cotizaciones`).
     - **Módulos de Repuestos:** Inventario y Lotes (`#inventario`), Punto de Venta (`#pos`).
     - **Sistema y Seguridad:** Actualizar Contraseña (`/actualizar-clave`), Cerrar Sesión (`/salir`).
   - El enlace de la página activa debe incluir la clase `active`.
2. **Topbar Superior (`.app-topbar`):**
   - Botón toggle de menú para móviles (`w3-hide-large`).
   - Título de la página con badge descriptivo (`.badge-pill.badge-red`).
   - Botón alternador de tema oscuro/claro (Sol / Luna).
   - Botón de acceso rápido a Cerrar Sesión.
3. **Contenido Principal (`.app-main` -> `<main>`):**
   - Migas de pan de navegación (`Dashboard > Módulo > Acción`).
   - Título principal y subtítulo de contexto.
   - Tarjetas de resumen métrico (`.kpi-card`).
   - Panel de datos o formulario (`.panel-card`).

## 3. Tablas y Búsqueda Interactiva

- Pasar los datos del backend a Alpine.js como lista serializada:
  ```twig
  suppliersList: [
    {% for item in items %}
    { id: {{ item.id }}, name: '{{ item.name|e('js') }}', ... }{% if not loop.last %},{% endif %}
    {% endfor %}
  ]
  ```
- Implementar un getter reactivo `filteredItems` que busque en múltiples campos a la vez.
- Ofrecer estado visual vacío (`empty state`) cuando no haya registros o cuando la búsqueda arroje cero resultados.
- Usar modales interactivos para confirmación antes de redireccionar a rutas destructivas (`/modulo/{id}/eliminar`).
