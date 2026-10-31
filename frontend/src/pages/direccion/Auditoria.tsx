import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Activity, LogIn, Pencil, Plus, Trash2, type LucideIcon } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { Avatar } from '@/components/Avatar';
import { BarraFiltros } from '@/components/BarraFiltros';
import { Encabezado } from '@/components/Encabezado';
import { Filtro, FiltroFecha } from '@/components/Filtro';
import { Paginador } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { BotonIcono } from '@/components/ui/boton';
import { Tarjeta } from '@/components/ui/tarjeta';
import { api, type Pagina } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion, useFiltrosUrl } from '@/lib/hooks';
import { cn, formato } from '@/lib/utils';

interface Registro { id: number; creado_en: string; accion: string; detalles: string | null; ip_origen: string | null; usuario: string | null }
interface Resumen { total: number; usuario_id: number; usuario: string }

const NOMBRES: Record<string, string> = {
  inicio_sesion: 'Inicio de sesión',
  cambio_contrasena: 'Cambio de contraseña',
  cambio_permisos: 'Cambio de permisos',
  editar_perfil: 'Edición de perfil',
  asistente_creado: 'Nuevo asistente',
  crear_matricula: 'Nueva matrícula',
  crear_apoderado: 'Nuevo familiar',
  editar_apoderado: 'Edición de familiar',
  eliminar_apoderado: 'Familiar eliminado',
  crear_concepto_pago: 'Nuevo concepto',
  editar_concepto_pago: 'Edición de concepto',
  eliminar_concepto_pago: 'Concepto eliminado',
  editar_estudiante: 'Edición de estudiante',
  eliminar_estudiante: 'Estudiante eliminado',
  actualizar_estado_academico: 'Cambio de estado',
  registrar_calificaciones: 'Notas registradas',
};
const legible = (accion: string) => NOMBRES[accion.toLowerCase()] ?? accion.replaceAll('_', ' ').replace(/^./, (c) => c.toUpperCase());

type Tipo = { icono: LucideIcon; tono: string };
function tipoDe(accion: string): Tipo {
  const a = accion.toLowerCase();
  if (a === 'inicio_sesion') return { icono: LogIn, tono: 'bg-slate-100 text-slate-500' };
  if (/^(eliminar|revoc)/.test(a)) return { icono: Trash2, tono: 'bg-red-50 text-red-600' };
  if (/^(crear|registr)|creado$/.test(a)) return { icono: Plus, tono: 'bg-emerald-50 text-emerald-600' };
  if (/^(editar|actualizar|cambio|acceso)/.test(a)) return { icono: Pencil, tono: 'bg-marca-50 text-marca-600' };
  return { icono: Activity, tono: 'bg-amber-50 text-amber-600' };
}

const hora = new Intl.DateTimeFormat('es-PE', { hour: 'numeric', minute: '2-digit' });
const dia = new Intl.DateTimeFormat('es-PE', { weekday: 'long', day: 'numeric', month: 'long' });
/** 'AAAA-MM-DD hh:mm:ss.uuuuuu' de la API a Date local (sin microsegundos). */
const aFecha = (valor: string) => { const [d, h] = valor.split(/[ T]/); return new Date(`${d}T${(h ?? '00:00:00').slice(0, 8)}`); };
const claveDia = (d: Date) => `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
function tituloDia(d: Date) {
  const hoy = new Date();
  const ayer = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 1);
  if (claveDia(d) === claveDia(hoy)) return 'Hoy';
  if (claveDia(d) === claveDia(ayer)) return 'Ayer';
  const texto = dia.format(d);
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/** Agrupa los registros (ya ordenados del más reciente al más antiguo) por día. */
function porDia(registros: Registro[]) {
  const grupos: { clave: string; titulo: string; items: Registro[] }[] = [];
  for (const r of registros) {
    const d = aFecha(r.creado_en);
    const clave = claveDia(d);
    if (grupos.at(-1)?.clave !== clave) grupos.push({ clave, titulo: tituloDia(d), items: [] });
    grupos.at(-1)!.items.push(r);
  }
  return grupos;
}

export default function Auditoria() {
  const { puede } = useSesion();
  const { filtros, pagina, cambiar, hayFiltros, limpiar } = useFiltrosUrl(['fecha_inicio', 'fecha_fin', 'usuario_id', 'tipo_accion'] as const);
  const usuarios = useQuery({ queryKey: ['usuarios'], queryFn: () => api.get<{ data: { id: number; nombres: string }[] }>('/usuarios').then((r) => r.data) });
  const resumen = useQuery({ queryKey: ['auditoria', 'resumen'], queryFn: () => api.get<{ data: Resumen[] }>('/auditoria/resumen').then((r) => r.data) });
  const consulta = useQuery({
    queryKey: ['auditoria', filtros, pagina],
    queryFn: () => api.get<Pagina<Registro>>('/auditoria', { ...filtros, pagina }),
    placeholderData: keepPreviousData,
  });
  const eliminar = useAccion((r: Registro) => api.delete(`/auditoria/${r.id}`), {
    exito: 'Registro eliminado.',
    invalidar: [['auditoria']],
  });
  const conBorrar = puede('p_auditoria');
  const totalAcciones = resumen.data?.reduce((s, r) => s + r.total, 0) ?? 0;
  const registros = consulta.data?.data ?? [];

  return (
    <>
      <Encabezado titulo="Auditoría" descripcion="Historial de cambios en el sistema." total={consulta.data?.meta.total} />

      {!!resumen.data?.length && (
        <div className="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {resumen.data.slice(0, 4).map((r) => {
            const elegido = filtros.usuario_id === String(r.usuario_id);
            return (
              <button
                key={r.usuario_id}
                type="button"
                onClick={() => cambiar({ usuario_id: elegido ? '' : r.usuario_id })}
                className={cn(
                  'flex items-center gap-3 rounded-xl border bg-white p-3.5 text-left shadow-[0_1px_2px_rgb(15_23_42/0.04)] transition-colors',
                  elegido ? 'border-marca-500 ring-3 ring-marca-100' : 'border-slate-200/80 hover:border-slate-300',
                )}
              >
                <Avatar nombre={r.usuario} foto={null} className="size-10" />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm text-slate-900">{r.usuario}</p>
                  <div className="mt-1.5 flex items-center gap-2">
                    <div className="h-1.5 flex-1 rounded-full bg-slate-100">
                      <div className="h-full rounded-full bg-marca-600" style={{ width: `${totalAcciones ? (r.total / totalAcciones) * 100 : 0}%` }} />
                    </div>
                    <span className="text-xs text-slate-500 tabular-nums">{formato.numero(r.total)}</span>
                  </div>
                </div>
              </button>
            );
          })}
        </div>
      )}

      <BarraFiltros activos={hayFiltros} alLimpiar={limpiar}>
        <FiltroFecha etiqueta="Desde" valor={filtros.fecha_inicio} alCambiar={(fecha_inicio) => cambiar({ fecha_inicio })} />
        <FiltroFecha etiqueta="Hasta" valor={filtros.fecha_fin} alCambiar={(fecha_fin) => cambiar({ fecha_fin })} />
        <Filtro etiqueta="Usuario" valor={filtros.usuario_id} opciones={usuarios.data?.map((u) => ({ valor: u.id, texto: u.nombres })) ?? []} alCambiar={(usuario_id) => cambiar({ usuario_id })} />
        <Filtro
          etiqueta="Acción"
          valor={filtros.tipo_accion}
          opciones={[{ valor: 'INSERT', texto: 'Registros nuevos' }, { valor: 'UPDATE', texto: 'Cambios' }, { valor: 'DELETE', texto: 'Eliminaciones' }]}
          alCambiar={(tipo_accion) => cambiar({ tipo_accion })}
        />
      </BarraFiltros>

      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}

      <Tarjeta className="overflow-hidden">
        {consulta.isPending && (
          <div className="space-y-4 p-5">
            {Array.from({ length: 6 }, (_, i) => (
              <div key={i} className="flex items-center gap-3">
                <div className="size-8 animate-pulse rounded-full bg-slate-100" />
                <div className="h-4 flex-1 animate-pulse rounded bg-slate-100" />
              </div>
            ))}
          </div>
        )}
        {!consulta.isPending && registros.length === 0 && <p className="px-5 py-12 text-center text-sm text-slate-500">No hay registros con esos filtros.</p>}
        {porDia(registros).map((grupo) => (
          <section key={grupo.clave}>
            <h2 className="sticky top-16 z-10 border-y border-slate-100 bg-slate-50/95 px-5 py-2 text-xs font-medium text-slate-500 backdrop-blur first:border-t-0">{grupo.titulo}</h2>
            <ul>
              {grupo.items.map((r) => {
                const { icono: Icono, tono } = tipoDe(r.accion);
                return (
                  <li key={r.id} className="group flex items-start gap-3 border-b border-slate-100 px-5 py-3 last:border-b-0 hover:bg-slate-50/60">
                    <span className={cn('mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-full', tono)}>
                      <Icono className="size-4" aria-hidden />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm text-slate-600">
                        <span className="font-medium text-slate-900">{r.usuario ?? 'Sistema'}</span>
                        <span className="mx-1.5 text-slate-300">·</span>
                        {legible(r.accion)}
                      </p>
                      {r.detalles && <p className="truncate text-sm text-slate-500" title={r.detalles}>{r.detalles}</p>}
                    </div>
                    <div className="shrink-0 text-right">
                      <p className="text-sm text-slate-600 tabular-nums">{hora.format(aFecha(r.creado_en))}</p>
                      {r.ip_origen && <p className="text-xs text-slate-400 tabular-nums">{r.ip_origen}</p>}
                    </div>
                    {conBorrar && (
                      <div className="shrink-0 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                        <BotonIcono peligro onClick={async () => (await confirmar({ titulo: 'Eliminar registro', texto: `Se eliminará el registro «${legible(r.accion)}». Esta acción no se puede deshacer.`, accion: 'Eliminar', peligro: true })) && eliminar.mutate(r)} etiqueta="Eliminar registro">
                          <Trash2 />
                        </BotonIcono>
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          </section>
        ))}
        <Paginador meta={consulta.data?.meta} alCambiar={(p) => cambiar({ pagina: p })} />
      </Tarjeta>
    </>
  );
}
