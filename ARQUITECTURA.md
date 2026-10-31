# Arquitectura

PHP se usa **solo como API JSON** (`/api/v1`). Sin framework: clases propias por capas, autoload PSR-4 de Composer, inyección de dependencias y tests con PHPUnit.

```
Sistema_escolar/
├── public/                 # ÚNICA carpeta expuesta por el servidor web (docroot)
│   ├── index.php           # Punto de entrada único (front controller)
│   └── .htaccess           # Todo lo que no es archivo → index.php; pasa Authorization
├── src/                    # Namespace App\
│   ├── Aplicacion.php      # Arma el contenedor, las rutas y el kernel
│   ├── Core/               # Config, Database (PDO tipado, transacciones, paginación), Contenedor
│   ├── Http/               # Kernel, Router, Request, Response, HttpException, Middleware/
│   ├── Auth/               # Tokens (ServicioTokens), Contexto del usuario, Rol, Permiso
│   ├── Validacion/         # Validador (un error por campo) y ValidacionException
│   ├── Soporte/            # Dinero (céntimos), Booleano, FiltroAcademico
│   └── Modulos/            # Un módulo por dominio
│       ├── Auth  Usuarios  Auditoria  Panel  Catalogos
│       └── Estudiantes  Responsables  Matriculas  Pagos  Academico
├── config/
│   └── rutas.php           # Tabla de rutas y el acceso que exige cada una
├── database/
│   ├── schema.sql  permissions.sql
│   └── migraciones/*.sql   # Cambios numerados (003 = tabla api_token)
├── bin/                    # migrar.php · crear-usuario.php
├── tests/Unit/             # PHPUnit: Validador, Router, Dinero, Kernel
├── frontend/               # SPA React + Vite + TypeScript (ver README)
└── doc-exel/               # Datos personales: fuera de Git y fuera de la web
```

## Capas

Cada módulo separa responsabilidades:

- **Controller**: lee y valida la entrada (`Validador`), llama al servicio y arma la `Response`. No contiene SQL.
- **Service**: reglas de negocio, transacciones y auditoría. Lanza `HttpException` (404, 409, 422...).
- **Repository**: solo SQL con parámetros enlazados; nunca concatena valores.

Los módulos de solo lectura (Panel, Catálogos, listados) van directo de Controller a Repository. El `Contenedor` crea cada clase resolviendo su constructor, así que agregar un módulo es crear sus clases y registrar sus rutas en `config/rutas.php`.

## Flujo de una petición

`public/index.php` → `Aplicacion::kernel()` → `Kernel::manejar()`: CORS → `Router::buscar()` → middlewares (`auth`, `rol:director`, `permiso:p_cobrar`) → controlador → `Response` JSON.

El Kernel convierte los errores en respuestas coherentes: `HttpException` con su código; errores de PostgreSQL en 409 (duplicado o vinculado) o 422 (dato inválido); cualquier otro en 500 sin filtrar detalles (se registran con `error_log`). Si había una transacción abierta, se revierte. La conexión a la base se abre en la primera consulta.

## Seguridad

- Autenticación con token Bearer aleatorio (256 bits). En la base solo se guarda su hash SHA-256 (`api_token`).
- El token vence tras `APP_TOKEN_HORAS` sin uso y se renueva al usarse. Se elimina al cerrar sesión, al cambiar la contraseña (salvo la sesión actual) y al revocar el acceso de un asistente.
- En cada petición se vuelven a leer el usuario y sus permisos: un cambio de permisos o una revocación se aplica de inmediato.
- Login sin filtración de información: el mismo mensaje y un tiempo similar, exista o no el correo.
- Roles: `asistente`, `director`, `hiperadmin`. Director e hiperadmin tienen todos los permisos; hiperadmin además pasa los filtros de rol.
- Permisos de asistentes: `p_editar`, `p_matricular`, `p_cobrar`, `p_auditoria`.
- Montos en céntimos (enteros); las cuentas de pago se bloquean (`FOR UPDATE`) al registrar un abono.
- No se borran datos con historial: un estudiante con pagos o notas, un concepto en uso o un responsable con estudiantes o pagos devuelven 409.

## Endpoints (prefijo `/api/v1`)

Todas las rutas exigen `Authorization: Bearer <token>`, salvo el login. "Personal" significa cualquier usuario autenticado.

| Método | Ruta | Acceso |
|---|---|---|
| POST | `/auth/login` | público |
| GET · POST · PUT | `/auth/yo` · `/auth/logout` · `/auth/password` | personal |
| GET | `/panel/metricas` | personal |
| GET | `/catalogos/anios` · `niveles` · `grados?nivel_id=` · `secciones?grado_id=` | personal |
| GET | `/estudiantes?q=&estado=&nivel_id=&grado_id=&seccion_id=` · `/estudiantes/{id}` | personal |
| GET | `/estudiantes/buscar?q=` · `/estudiantes/{id}/datos-matricula` | p_matricular |
| PUT · PATCH · DELETE | `/estudiantes/{id}` · `/estudiantes/{id}/estado` · `/estudiantes/{id}` | p_matricular |
| GET | `/responsables?q=&nivel_id=...` · `/responsables/buscar?q=` · `/responsables/{id}` | personal |
| GET | `/responsables/{id}/candidatos-apoderado?estudiante_id=` | p_editar |
| POST · PUT · DELETE | `/responsables` · `/responsables/{id}` · `/responsables/{id}` | p_editar |
| DELETE | `/responsables/{id}/estudiantes/{estudiante}?reemplazo_id=` (desvincular) | p_editar |
| GET · POST | `/matriculas?q=&anio=&nivel_id=...` · `/matriculas` | personal · p_matricular |
| GET | `/pagos?q=` · `/conceptos-pago?todos=1` | personal |
| GET | `/pagos/estudiantes?q=` · `/pagos/matriculas/{id}/apoderado` · `/pagos/saldo?matricula_id=&concepto_id=` | p_cobrar |
| POST | `/pagos` | p_cobrar |
| POST · PUT · PATCH · DELETE | `/conceptos-pago` · `/conceptos-pago/{id}` · `/conceptos-pago/{id}/estado` · `/conceptos-pago/{id}` | p_cobrar |
| GET | `/cursos` · `/periodos` | personal |
| POST · PUT · DELETE | `/cursos[/{id}]` · `/periodos[/{id}]` (DELETE desactiva) | p_matricular |
| GET · PUT | `/calificaciones?curso_id=&periodo_id=[&grado_seccion_id=]` · `/calificaciones` | p_matricular |
| GET | `/usuarios` · `/permisos` · `/usuarios/asistentes` | director |
| POST · PUT | `/usuarios/asistentes` · `/usuarios/asistentes/{id}/permisos` | director |
| POST | `/usuarios/asistentes/{id}/revocar` · `/restaurar` | director |
| GET | `/auditoria?fecha_inicio=&fecha_fin=&usuario_id=&tipo_accion=` · `/auditoria/resumen` | director |
| DELETE | `/auditoria/{id}` | p_auditoria |

### Cuerpos principales

- `POST /matriculas`: `{ estudiante_id | estudiante: {dni, nombres, apellido_paterno, apellido_materno}, hermano_id?, padre?, madre?, emergencia?, apoderado_principal: "padre"|"madre"|"emergencia", anio, grado_seccion_id }`. Cada contacto es `{dni, nombres, apellido_paterno, apellido_materno?, celular?}`; emergencia exige además `parentesco` y `celular`.
- `POST /pagos`: `{ matricula_id, concepto_id, abono_hoy: "50.00", entregado_por, fecha_pago: "AAAA-MM-DD", observacion? }`. Los conceptos `familia` comparten una cuenta por apoderado y año.
- `PUT /calificaciones`: `{ curso_id, periodo_id, notas: { "<matricula_id>": "15.5" } }` (0 a 20, hasta 2 decimales; las vacías se ignoran).

## Pendientes conocidos

- `curso.grado` es texto libre: las calificaciones no se filtran por el grado del curso (usar `grado_seccion_id`).
