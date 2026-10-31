<?php
declare(strict_types=1);

/**
 * Rutas de la API (todas bajo /api/v1).
 * Middlewares: 'auth' (token Bearer válido), 'rol:director' (director o hiperadmin), 'permiso:<código>'.
 * El director y el hiperadmin tienen todos los permisos; el asistente solo los que se le otorgaron.
 */

use App\Http\Router;
use App\Modulos\Academico\AcademicoController as Academico;
use App\Modulos\Auditoria\AuditoriaController as Auditoria;
use App\Modulos\Auth\AuthController as Auth;
use App\Modulos\Catalogos\CatalogoController as Catalogos;
use App\Modulos\Estudiantes\EstudianteController as Estudiantes;
use App\Modulos\Matriculas\MatriculaController as Matriculas;
use App\Modulos\Pagos\ConceptoController as Conceptos;
use App\Modulos\Pagos\PagoController as Pagos;
use App\Modulos\Panel\PanelController as Panel;
use App\Modulos\Responsables\ResponsableController as Responsables;
use App\Modulos\Usuarios\UsuarioController as Usuarios;

return static function (Router $r): void {
    $r->grupo('/api/v1', [], static function (Router $r): void {
        $r->post('/auth/login', [Auth::class, 'login']);

        $r->grupo('', ['auth'], static function (Router $r): void {
            $editar = ['permiso:p_editar'];
            $matricular = ['permiso:p_matricular'];
            $cobrar = ['permiso:p_cobrar'];

            // Sesión
            $r->get('/auth/yo', [Auth::class, 'yo']);
            $r->post('/auth/logout', [Auth::class, 'logout']);
            $r->put('/auth/password', [Auth::class, 'cambiarContrasena']);
            $r->put('/auth/perfil', [Auth::class, 'actualizarPerfil']);
            $r->put('/auth/foto', [Auth::class, 'cambiarFoto']);
            $r->delete('/auth/foto', [Auth::class, 'quitarFoto']);

            // Panel y catálogos
            $r->get('/panel/metricas', [Panel::class, 'metricas']);
            $r->get('/panel/analiticas', [Panel::class, 'analiticas']);
            $r->get('/catalogos/anios', [Catalogos::class, 'anios']);
            $r->get('/catalogos/niveles', [Catalogos::class, 'niveles']);
            $r->get('/catalogos/grados', [Catalogos::class, 'grados']);
            $r->get('/catalogos/secciones', [Catalogos::class, 'secciones']);

            // Estudiantes
            $r->get('/estudiantes', [Estudiantes::class, 'listar']);
            $r->get('/estudiantes/buscar', [Estudiantes::class, 'buscar'], $matricular);
            $r->get('/estudiantes/{id}', [Estudiantes::class, 'ver']);
            $r->get('/estudiantes/{id}/datos-matricula', [Estudiantes::class, 'datosMatricula'], $matricular);
            $r->put('/estudiantes/{id}', [Estudiantes::class, 'actualizar'], $matricular);
            $r->patch('/estudiantes/{id}/estado', [Estudiantes::class, 'cambiarEstado'], $matricular);
            $r->delete('/estudiantes/{id}', [Estudiantes::class, 'eliminar'], $matricular);

            // Responsables (padres, madres, apoderados, contactos de emergencia)
            $r->get('/responsables', [Responsables::class, 'listar']);
            $r->get('/responsables/buscar', [Responsables::class, 'buscar']);
            $r->get('/responsables/{id}', [Responsables::class, 'ver']);
            $r->get('/responsables/{id}/candidatos-apoderado', [Responsables::class, 'candidatosApoderado'], $editar);
            $r->post('/responsables', [Responsables::class, 'crear'], $editar);
            $r->put('/responsables/{id}', [Responsables::class, 'actualizar'], $editar);
            $r->delete('/responsables/{id}', [Responsables::class, 'eliminar'], $editar);
            $r->delete('/responsables/{id}/estudiantes/{estudiante}', [Responsables::class, 'desvincular'], $editar);

            // Matrículas
            $r->get('/matriculas', [Matriculas::class, 'listar']);
            $r->post('/matriculas', [Matriculas::class, 'crear'], $matricular);

            // Pagos y conceptos de pago
            $r->get('/pagos', [Pagos::class, 'listar']);
            $r->get('/pagos/estudiantes', [Pagos::class, 'buscarEstudiantes'], $cobrar);
            $r->get('/pagos/saldo', [Pagos::class, 'saldo'], $cobrar);
            $r->get('/pagos/matriculas/{id}/apoderado', [Pagos::class, 'apoderado'], $cobrar);
            $r->post('/pagos', [Pagos::class, 'registrar'], $cobrar);
            $r->get('/conceptos-pago', [Conceptos::class, 'listar']);
            $r->post('/conceptos-pago', [Conceptos::class, 'crear'], $cobrar);
            $r->put('/conceptos-pago/{id}', [Conceptos::class, 'actualizar'], $cobrar);
            $r->patch('/conceptos-pago/{id}/estado', [Conceptos::class, 'cambiarEstado'], $cobrar);
            $r->delete('/conceptos-pago/{id}', [Conceptos::class, 'eliminar'], $cobrar);

            // Académico
            $r->get('/cursos', [Academico::class, 'cursos']);
            $r->post('/cursos', [Academico::class, 'crearCurso'], $matricular);
            $r->put('/cursos/{id}', [Academico::class, 'actualizarCurso'], $matricular);
            $r->delete('/cursos/{id}', [Academico::class, 'desactivarCurso'], $matricular);
            $r->get('/periodos', [Academico::class, 'periodos']);
            $r->post('/periodos', [Academico::class, 'crearPeriodo'], $matricular);
            $r->put('/periodos/{id}', [Academico::class, 'actualizarPeriodo'], $matricular);
            $r->delete('/periodos/{id}', [Academico::class, 'desactivarPeriodo'], $matricular);
            $r->get('/calificaciones', [Academico::class, 'calificaciones'], $matricular);
            $r->put('/calificaciones', [Academico::class, 'guardarCalificaciones'], $matricular);

            // Auditoría: eliminar un registro requiere el permiso específico.
            $r->delete('/auditoria/{id}', [Auditoria::class, 'eliminar'], ['permiso:p_auditoria']);

            // Dirección
            $r->grupo('', ['rol:director'], static function (Router $r): void {
                $r->get('/usuarios', [Usuarios::class, 'listar']);
                $r->get('/permisos', [Usuarios::class, 'permisos']);
                $r->get('/usuarios/asistentes', [Usuarios::class, 'asistentes']);
                $r->post('/usuarios/asistentes', [Usuarios::class, 'crearAsistente']);
                $r->put('/usuarios/asistentes/{id}/permisos', [Usuarios::class, 'actualizarPermisos']);
                $r->post('/usuarios/asistentes/{id}/revocar', [Usuarios::class, 'revocar']);
                $r->post('/usuarios/asistentes/{id}/restaurar', [Usuarios::class, 'restaurar']);
                $r->get('/auditoria', [Auditoria::class, 'listar']);
                $r->get('/auditoria/resumen', [Auditoria::class, 'resumen']);
            });
        });
    });
};
