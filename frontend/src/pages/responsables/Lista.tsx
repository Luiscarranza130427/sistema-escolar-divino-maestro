import { useState } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router';
import { UserPlus } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { BarraFiltros } from '@/components/BarraFiltros';
import { Buscador } from '@/components/Buscador';
import { Encabezado } from '@/components/Encabezado';
import { FiltroAcademico } from '@/components/FiltroAcademico';
import { EstadoFilas, Paginador, Tabla, Td, Th } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { Boton } from '@/components/ui/boton';
import { Insignia } from '@/components/ui/insignia';
import { api, type Pagina } from '@/lib/api';
import { useFiltrosUrl } from '@/lib/hooks';
import { formato, nombreCompleto } from '@/lib/utils';
import { FormResponsable } from './FormResponsable';

interface ResponsableFila {
  id: number;
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  celular: string | null;
  relacion_id: number | null;
  parentesco: string | null;
  relacion_tipo: string | null;
  es_apoderado: boolean | null;
  estudiante_id: number | null;
  estudiante_nombres: string | null;
  estudiante_apellido_paterno: string | null;
  estudiante_apellido_materno: string | null;
  grado: string | null;
  seccion: string | null;
  nivel: string | null;
  pagos_total: string | null;
}

export default function ListaResponsables() {
  const { puede } = useSesion();
  const navigate = useNavigate();
  const [creando, setCreando] = useState(false);
  const { filtros, pagina, cambiar, hayFiltros, limpiar } = useFiltrosUrl(['q', 'nivel_id', 'grado_id', 'seccion_id'] as const);
  const consulta = useQuery({
    queryKey: ['responsables', filtros, pagina],
    queryFn: () => api.get<Pagina<ResponsableFila>>('/responsables', { ...filtros, pagina }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <Encabezado
        titulo="Familias"
        descripcion="Padres, madres y apoderados." total={consulta.data?.meta.total}
        acciones={
          puede('p_editar') && (
            <Boton onClick={() => setCreando(true)}>
              <UserPlus className="size-4" aria-hidden /> Nuevo familiar
            </Boton>
          )
        }
      />

      <BarraFiltros activos={hayFiltros} alLimpiar={limpiar}>
        <Buscador valor={filtros.q} alCambiar={(q) => cambiar({ q })} placeholder="Buscar familiar, alumno o DNI" />
        <FiltroAcademico valores={filtros} alCambiar={cambiar} />
      </BarraFiltros>

      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}

      <Tabla pie={<Paginador meta={consulta.data?.meta} alCambiar={(p) => cambiar({ pagina: p })} />}>
        <thead>
          <tr>
            <Th>Familiar</Th>
            <Th>Celular</Th>
            <Th>Estudiante</Th>
            <Th>Grado</Th>
            <Th className="text-right">Pagado</Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={consulta.isPending} vacio={consulta.data?.data.length === 0} columnas={5} mensaje="No se encontraron familias con esos filtros." />
          {consulta.data?.data.map((r) => (
            <tr key={`${r.id}-${r.relacion_id ?? 0}`} onClick={() => navigate(`/responsables/${r.id}`)} className="cursor-pointer hover:bg-slate-50">
              <Td>
                <Link to={`/responsables/${r.id}`} onClick={(e) => e.stopPropagation()} className="text-slate-900 hover:text-marca-600">
                  {nombreCompleto(r, true)}
                </Link>
                <span className="block text-xs text-slate-500">DNI {r.dni}</span>
              </Td>
              <Td className="text-slate-600 tabular-nums">{r.celular || '—'}</Td>
              <Td>
                {r.estudiante_id ? (
                  <>
                    <span className="text-slate-900">
                      {nombreCompleto({ nombres: r.estudiante_nombres ?? '', apellido_paterno: r.estudiante_apellido_paterno ?? '', apellido_materno: r.estudiante_apellido_materno }, true)}
                    </span>
                    <span className="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                      {r.parentesco ?? r.relacion_tipo}
                      {r.es_apoderado && <Insignia tono="azul">Apoderado</Insignia>}
                    </span>
                  </>
                ) : (
                  <span className="text-slate-400">Sin estudiantes</span>
                )}
              </Td>
              <Td className="text-slate-600">{r.grado ? `${r.nivel} · ${r.grado} ${r.seccion}` : '—'}</Td>
              <Td className="text-right tabular-nums">{formato.soles(r.pagos_total ?? 0)}</Td>
            </tr>
          ))}
        </tbody>
      </Tabla>

      {creando && <FormResponsable abierto={creando} alCerrar={() => setCreando(false)} alGuardar={(id) => navigate(`/responsables/${id}`)} />}
    </>
  );
}
