import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link, useNavigate, useParams } from 'react-router';
import { ArrowLeft, Pencil, Trash2, Unlink } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { Encabezado } from '@/components/Encabezado';
import { Cargando, Datos, Seccion } from '@/components/Seccion';
import { AlertaError } from '@/components/ui/alerta';
import { Insignia } from '@/components/ui/insignia';
import { Boton, BotonIcono } from '@/components/ui/boton';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import { fecha, formato, nombreCompleto } from '@/lib/utils';
import { Desvincular } from './Desvincular';
import { FormResponsable } from './FormResponsable';

interface FichaResponsable {
  responsable: {
    id: number; dni: string; nombres: string; apellido_paterno: string; apellido_materno: string | null; nombre_padre_adicional: string | null;
    direccion: string | null; celular: string | null; celular_padre: string | null; celular_madre: string | null; correo: string | null;
    nro_tarjeta_apafa: string | null; creado_en: string;
  };
  estudiantes: { id: number; dni: string; nombres: string; apellido_paterno: string; apellido_materno: string; relacion_id: number; tipo: string; parentesco: string | null; es_apoderado: boolean; anio: number | null; nivel: string | null; grado: string | null; seccion: string | null }[];
  pagos: { id: number; fecha_pago: string; monto: string; nro_recibo: string | null; concepto: string; estudiante_id: number; estudiante_nombres: string; estudiante_apellido_paterno: string }[];
}

export default function FichaResponsablePagina() {
  const { id = '' } = useParams();
  const { puede } = useSesion();
  const navigate = useNavigate();
  const [editando, setEditando] = useState(false);
  const [quitando, setQuitando] = useState<{ id: number; nombre: string; es_apoderado: boolean } | null>(null);
  const consulta = useQuery({ queryKey: ['responsable', id], queryFn: () => api.get<{ data: FichaResponsable }>(`/responsables/${id}`).then((r) => r.data) });
  const eliminar = useAccion(() => api.delete(`/responsables/${id}`), {
    exito: 'Familiar eliminado.',
    invalidar: [['responsables']],
    alTerminar: () => navigate('/responsables', { replace: true }),
  });

  if (consulta.error) return <AlertaError>{consulta.error.message}</AlertaError>;
  if (!consulta.data) return <Cargando />;
  const { responsable: r, estudiantes, pagos } = consulta.data;
  const editable = puede('p_editar');
  const total = pagos.reduce((s, p) => s + Number(p.monto), 0);

  return (
    <>
      <Link to="/responsables" className="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <ArrowLeft className="size-4" aria-hidden /> Familias
      </Link>
      <Encabezado
        titulo={nombreCompleto(r)}
        descripcion={`DNI ${r.dni}`}
        acciones={
          editable && (
            <>
              <Boton variante="secundario" onClick={() => setEditando(true)}><Pencil className="size-4" aria-hidden /> Editar</Boton>
              <Boton variante="peligroSuave" onClick={async () => (await confirmar({ titulo: 'Eliminar familiar', texto: `Se eliminará a ${nombreCompleto(r)}. Solo es posible si no tiene estudiantes vinculados ni pagos o matrículas registradas.`, accion: 'Eliminar', peligro: true })) && eliminar.mutate(undefined)}>
                <Trash2 aria-hidden /> Eliminar
              </Boton>
            </>
          )
        }
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Seccion titulo="Datos de contacto">
            <Datos
              items={[
                ['Celular', r.celular],
                ['Correo', r.correo],
                ['Dirección', r.direccion],
                ['N.º tarjeta APAFA', r.nro_tarjeta_apafa],
                ['Celular del padre', r.celular_padre],
                ['Celular de la madre', r.celular_madre],
              ]}
            />
          </Seccion>
          <Seccion titulo="Estudiantes a cargo">
            {estudiantes.length === 0 ? (
              <p className="text-sm text-slate-500">No está vinculado a ningún estudiante.</p>
            ) : (
              <ul className="divide-y divide-slate-100">
                {estudiantes.map((e) => (
                  <li key={e.relacion_id} className="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div>
                      <Link to={`/estudiantes/${e.id}`} className="text-slate-900 hover:text-marca-600">{nombreCompleto(e)}</Link>
                      <p className="text-sm text-slate-500">
                        {e.parentesco ?? e.tipo}{e.grado ? ` · ${e.nivel} ${e.grado} ${e.seccion} (${e.anio})` : ''}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      {e.es_apoderado && <Insignia tono="azul">Apoderado</Insignia>}
                      {editable && (
                        <BotonIcono onClick={() => setQuitando({ id: e.id, nombre: nombreCompleto(e), es_apoderado: e.es_apoderado })} etiqueta={`Quitar vínculo con ${nombreCompleto(e)}`}>
                          <Unlink aria-hidden />
                        </BotonIcono>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </Seccion>
        </div>
        <Seccion titulo={`Pagos realizados · ${formato.soles(total)}`}>
          {pagos.length === 0 ? (
            <p className="text-sm text-slate-500">Sin pagos registrados.</p>
          ) : (
            <ul className="divide-y divide-slate-100 text-sm">
              {pagos.map((p) => (
                <li key={p.id} className="flex justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                  <div>
                    <p className="text-slate-900">{p.concepto}</p>
                    <p className="text-slate-500">{fecha(p.fecha_pago)} · {p.estudiante_nombres} {p.estudiante_apellido_paterno}</p>
                  </div>
                  <span className="tabular-nums text-slate-900">{formato.soles(p.monto)}</span>
                </li>
              ))}
            </ul>
          )}
        </Seccion>
      </div>

      {editando && (
        <FormResponsable
          abierto={editando}
          alCerrar={() => setEditando(false)}
          inicial={{ id: r.id, dni: r.dni, nombres: r.nombres, apellido_paterno: r.apellido_paterno, apellido_materno: r.apellido_materno ?? '', direccion: r.direccion ?? '', celular: r.celular ?? '', nro_tarjeta_apafa: r.nro_tarjeta_apafa ?? '' }}
        />
      )}
      {quitando && <Desvincular responsableId={r.id} estudiante={quitando} alCerrar={() => setQuitando(null)} />}
    </>
  );
}
