# Reglas de Calidad de Código y Commits

## 1. Verificación de Calidad

Antes de finalizar cualquier tarea o cambio en código PHP o plantillas:
- **Linter & Análisis Estático:** Ejecutar `composer lint`. Este comando ejecuta Mago (`mago lint`, `mago analyze`, `mago guard`). Todos los errores en archivos nuevos o modificados deben ser resueltos antes de dar por terminada la tarea.
- **Formateo:** Ejecutar `composer format` (`mago format`).
- **Pruebas Automatizadas:** Ejecutar `composer test` (`phpunit`). Ningún cambio debe romper las pruebas unitarias existentes.

## 2. Convención de Commits

Seguir el estándar **Conventional Commits** en inglés:
- Formato: `<tipo>(<alcance>): <descripción corta en presente>`
- Tipos comunes:
  - `feat`: Nueva funcionalidad o módulo (ej: `feat(suppliers): implement full CRUD...`).
  - `fix`: Corrección de un fallo (ej: `fix(auth): correct password hash comparison`).
  - `refactor`: Cambio de estructura sin alterar comportamiento externo.
  - `style`: Cambios de diseño, formato visual o CSS.
  - `build`: Actualización de dependencias o configuración en `composer.json`.
- Cuerpo descriptivo con bullets detallando los archivos o cambios clave cuando el commit abarque múltiples componentes.
