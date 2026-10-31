import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router';
import { ClipboardPlus } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { BarraFiltros } from '@/components/BarraFiltros';
import { Buscador } from '@/components/Buscador';
import { Encabezado } from '@/components/Encabezado';
import { Filtro } from '@/components/Filtro';
import { FiltroAcademico } from '@/components/FiltroAcademico';
import { EstadoFilas, Paginador, Tabla, Td, Th } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { Insignia, type Tono } from '@/components/ui/insignia';
import { BotonEnlace } from '@/components/ui/boton';
import { api, type Pagina } from '@/lib/api';
import { useFiltrosUrl } from '@/lib/hooks';
import type { EstadoAcademico, EstudianteFila } from '@/lib/tipos';
import { nombreCompleto } from '@/lib/utils';

export const TONO_ESTADO: Record<EstadoAcademico, Tono> = { Activo: 'verde', Retirado: 'gris', Trasladado: 'ambar' };

export default function ListaEstudiantes() {
  const { puede } = useSesion();
  const navigate = useNavigate();
  const { filtros, pagina, cambiar, hayFiltros, limpiar } = useFiltrosUrl(['q', 'estado', 'nivel_id', 'grado_id', 'seccion_id'] as const);
  const consulta = useQuery({
    queryKey: ['estudiantes', filtros, pagina],
    queryFn: () => api.get<Pagina<EstudianteFila>>('/estudiantes', { ...filtros, pagina }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <Encabezado
        titulo="Estudiantes"
        descripcion="Fichas, familia y pagos de cada alumno." total={consulta.data?.meta.total}
        acciones={
          puede('p_matricular') && (
            <BotonEnlace to="/matriculas/nueva">
              <ClipboardPlus aria-hidden /> Nueva matrícula
            </BotonEnlace>
          )
        }
      />

      <BarraFiltros activos={hayFiltros} alLimpiar={limpiar}>
        <Buscador valor={filtros.q} alCambiar={(q) => cambiar({ q })} placeholder="Buscar por nombre o DNI" />
        <Filtro
          etiqueta="Estado"
          porDefecto="Activos"
          valor={filtros.estado}
          opciones={[{ valor: 'Retirado', texto: 'Retirados' }, { valor: 'Trasladado', texto: 'Trasladados' }]}
          alCambiar={(estado) => cambiar({ estado })}
        />
        <FiltroAcademico valores={filtros} alCambiar={cambiar} />
      </BarraFiltros>

      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}

      <Tabla pie={<Paginador meta={consulta.data?.meta} alCambiar={(p) => cambiar({ pagina: p })} />}>
        <thead>
          <tr>
            <Th>Estudiante</Th>
            <Th>DNI</Th>
            <Th>Grado actual</Th>
            <Th>Estado</Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={consulta.isPending} vacio={consulta.data?.data.length === 0} columnas={4} mensaje="No se encontraron estudiantes con esos filtros." />
          {consulta.data?.data.map((e) => (
            <tr key={e.id} onClick={() => navigate(`/estudiantes/${e.id}`)} className="cursor-pointer hover:bg-slate-50">
              <Td>
                <Link to={`/estudiantes/${e.id}`} className="text-slate-900 hover:text-marca-600" onClick={(ev) => ev.stopPropagation()}>
                  {nombreCompleto(e, true)}
                </Link>
              </Td>
              <Td className="text-slate-600 tabular-nums">{e.dni}</Td>
              <Td className="text-slate-600">{e.grado ? `${e.nivel} · ${e.grado} ${e.seccion} · ${e.anio}` : <span className="text-slate-400">Sin matrícula</span>}</Td>
              <Td>
                <Insignia tono={TONO_ESTADO[e.estado_academico]}>{e.estado_academico}</Insignia>
              </Td>
            </tr>
          ))}
        </tbody>
      </Tabla>
    </>
  );
}
