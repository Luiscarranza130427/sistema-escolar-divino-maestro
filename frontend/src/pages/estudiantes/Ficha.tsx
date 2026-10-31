import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate, useParams } from 'react-router';
import { ArrowLeft, ClipboardPlus, Pencil, Trash2 } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { Encabezado } from '@/components/Encabezado';
import { Cargando, Datos, Seccion } from '@/components/Seccion';
import { AlertaError } from '@/components/ui/alerta';
import { Insignia } from '@/components/ui/insignia';
import { Selector } from '@/components/ui/selector';
import { Boton, BotonEnlace } from '@/components/ui/boton';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import type { EstadoAcademico } from '@/lib/tipos';
import { fecha, formato, nombreCompleto } from '@/lib/utils';
import { EditarEstudiante } from './EditarEstudiante';
import { TONO_ESTADO } from './Lista';
import type { FichaEstudiante } from './tipos';

function Vacio({ children }: { children: string }) {
  return <p className="text-sm text-slate-500">{children}</p>;
}

export default function FichaEstudiantePagina() {
  const { id = '' } = useParams();
  const { puede } = useSesion();
  const navigate = useNavigate();
  const [editando, setEditando] = useState(false);
  const consulta = useQuery({ queryKey: ['estudiante', id], queryFn: () => api.get<{ data: FichaEstudiante }>(`/estudiantes/${id}`).then((r) => r.data) });

  const cambiarEstado = useAccion((estado_academico: EstadoAcademico) => api.patch(`/estudiantes/${id}/estado`, { estado_academico }), {
    exito: 'Estado actualizado.',
    invalidar: [['estudiante', id], ['estudiantes']],
  });
  const eliminar = useAccion(() => api.delete(`/estudiantes/${id}`), {
    exito: 'Estudiante eliminado.',
    invalidar: [['estudiantes']],
    alTerminar: () => navigate('/estudiantes', { replace: true }),
  });

  if (consulta.error) return <AlertaError>{consulta.error.message}</AlertaError>;
  if (!consulta.data) return <Cargando />;
  const { estudiante: e, responsables, matriculas, hermanos, pagos } = consulta.data;
  const editable = puede('p_matricular');

  return (
    <>
      <Link to="/estudiantes" className="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <ArrowLeft className="size-4" aria-hidden /> Estudiantes
      </Link>
      <Encabezado
        titulo={nombreCompleto(e)}
        descripcion={`DNI ${e.dni}`}
        acciones={
          editable && (
            <>
              <BotonEnlace variante="secundario" to={`/matriculas/nueva?estudiante_id=${e.id}`}>
                <ClipboardPlus aria-hidden /> Matricular
              </BotonEnlace>
              <Boton variante="secundario" onClick={() => setEditando(true)}>
                <Pencil aria-hidden /> Editar
              </Boton>
              <Boton variante="peligroSuave" onClick={async () => (await confirmar({ titulo: 'Eliminar estudiante', texto: `Se eliminará a ${nombreCompleto(e)} con sus matrículas. No es posible si ya tiene pagos o notas registradas.`, accion: 'Eliminar', peligro: true })) && eliminar.mutate(undefined)}>
                <Trash2 aria-hidden /> Eliminar
              </Boton>
            </>
          )
        }
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Seccion titulo="Datos personales">
            <Datos
              items={[
                ['Apellidos', `${e.apellido_paterno} ${e.apellido_materno}`],
                ['Nombres', e.nombres],
                ['Fecha de nacimiento', fecha(e.fecha_nacimiento)],
                ['Registrado', fecha(e.creado_en)],
              ]}
            />
            <div className="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 text-sm">
              <span className="text-slate-500">Estado académico</span>
              {editable ? (
                <Selector
                  aria-label="Estado académico"
                  value={e.estado_academico}
                  disabled={cambiarEstado.isPending}
                  onChange={async (ev) => { const estado = ev.target.value as EstadoAcademico; if (await confirmar({ titulo: 'Cambiar estado académico', texto: `${nombreCompleto(e)} pasará a estado «${estado}».`, accion: 'Cambiar' })) cambiarEstado.mutate(estado); }}
                  className="w-40"
                >
                  <option>Activo</option>
                  <option>Retirado</option>
                  <option>Trasladado</option>
                </Selector>
              ) : (
                <Insignia tono={TONO_ESTADO[e.estado_academico]}>{e.estado_academico}</Insignia>
              )}
            </div>
          </Seccion>

          <Seccion titulo="Familia">
            {responsables.length === 0 ? (
              <Vacio>Sin familiares registrados.</Vacio>
            ) : (
              <ul className="divide-y divide-slate-100">
                {responsables.map((r) => (
                  <li key={r.relacion_id} className="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                    <div>
                      <Link to={`/responsables/${r.id}`} className="text-slate-900 hover:text-marca-600">
                        {nombreCompleto(r)}
                      </Link>
                      <p className="text-sm text-slate-500">
                        {r.parentesco ?? r.tipo} · DNI {r.dni}
                        {r.celular && ` · ${r.celular}`}
                      </p>
                    </div>
                    {r.es_apoderado && <Insignia tono="azul">Apoderado</Insignia>}
                  </li>
                ))}
              </ul>
            )}
          </Seccion>

          <Seccion titulo="Pagos">
            {pagos.length === 0 ? (
              <Vacio>Sin pagos registrados.</Vacio>
            ) : (
              <ul className="divide-y divide-slate-100 text-sm">
                {pagos.map((p) => (
                  <li key={p.id} className="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                    <div>
                      <p className="text-slate-900">{p.concepto}</p>
                      <p className="text-slate-500">
                        {fecha(p.fecha_pago)}
                        {p.nro_recibo && ` · Recibo ${p.nro_recibo}`}
                      </p>
                    </div>
                    <span className="tabular-nums text-slate-900">{formato.soles(p.monto)}</span>
                  </li>
                ))}
              </ul>
            )}
          </Seccion>
        </div>

        <div className="space-y-6">
          <Seccion titulo="Matrículas">
            {matriculas.length === 0 ? (
              <Vacio>Nunca se ha matriculado.</Vacio>
            ) : (
              <ul className="space-y-3 text-sm">
                {matriculas.map((m) => (
                  <li key={m.id} className="flex items-center justify-between gap-2">
                    <div>
                      <p className="text-slate-900">{m.anio}</p>
                      <p className="text-slate-500">
                        {m.nivel} · {m.grado} {m.seccion}
                      </p>
                    </div>
                    <Insignia tono={m.estado === 'Activa' ? 'verde' : 'gris'}>{m.estado}</Insignia>
                  </li>
                ))}
              </ul>
            )}
          </Seccion>
          <Seccion titulo="Hermanos">
            {hermanos.length === 0 ? (
              <Vacio>Sin hermanos registrados.</Vacio>
            ) : (
              <ul className="space-y-2 text-sm">
                {hermanos.map((h) => (
                  <li key={h.id}>
                    <Link to={`/estudiantes/${h.id}`} className="text-slate-900 hover:text-marca-600">
                      {nombreCompleto(h)}
                    </Link>
                    <span className="text-slate-500"> · DNI {h.dni}</span>
                  </li>
                ))}
              </ul>
            )}
          </Seccion>
        </div>
      </div>

      {editando && <EditarEstudiante estudiante={e} abierto={editando} alCerrar={() => setEditando(false)} />}
    </>
  );
}
