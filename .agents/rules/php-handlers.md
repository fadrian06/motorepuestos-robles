# Reglas para Handlers HTTP (PHP 8.4 PSR-15)

Todos los controladores del sistema se implementan como handlers PSR-15 individuales bajo `app/Http/Handlers/` siguiendo el principio de responsabilidad única (Single Responsibility).

## 1. Declaración y Estructura Base

- Declarar siempre `declare(strict_types=1);` al inicio.
- La clase debe ser `final readonly class [Action] implements RequestHandlerInterface`.
- Utilizar atributos `#[Override]` y `#[NoDiscard]` en el método `handle`.
- **Gestión de Dependencias:**
  - Importar todas las dependencias mediante sentencias `use` al inicio del archivo.
  - En el constructor, utilizar los nombres de clase (class names) en lugar de nombres completamente calificados (FQCN) para los tipos de los parámetros.
  - Inyección de dependencias mediante constructor promotion readonly:
    ```php
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Form $form,
        private Flash $flash,
        private Auth $auth,
        private Environment $environment,
    ) {}
    ```

## 2. Flujo de Peticiones GET y POST

- En handlers con formulario, bifurcar mediante `$request->getMethod()`:
  - **GET:** Recuperar mensajes flash con `$flash = $this->flash->display();`, renderizar la plantilla Twig pasando `$flash` y los datos del modelo, y responder con status 200.
  - **POST:** 
    1. Obtener datos con `$request->getParsedBody()`.
    2. Validar con `$this->form->validate($body, [ ... ])`.
    3. Si maneja renglones múltiples o lotes (`product_id[]`, `quantity[]`, `cost[]`), validar cada elemento individualmente.
    4. Si falla la validación, guardar errores con `$this->flash->set($this->form->errors())` y responder con redirect header `location` al formulario.
    5. Para operaciones compuestas (ej: compras con ítems), envolver en transacción PDO (`$pdo->beginTransaction()`, `$pdo->commit()`, `$pdo->rollBack()`). Si falla, guardar `$db->errors()` en flash y redirigir.
    6. Si tiene éxito, redirigir al listado o siguiente vista con header `location`.

## 3. Manejo de Parámetros de Ruta

- Obtener los parámetros capturados por el router mediante atributos de la petición:
  ```php
  $id = (int) $request->getAttribute('id');
  ```
- Si el registro no existe en la base de datos, responder inmediatamente con un código 404:
  ```php
  return $this->responseFactory->createResponse(404);
  ```

## 4. Prohibición Estricta

- **NUNCA dejar llamadas de depuración en handlers** (`dd()`, `var_dump()`, `print_r()`, `die()`). Mago Lint emitirá un error crítico si se detectan estos símbolos.
