import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';
import { ClipboardPlus } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { BarraFiltros } from '@/components/BarraFiltros';
import { Buscador } from '@/components/Buscador';
import { Encabezado } from '@/components/Encabezado';
import { Filtro } from '@/components/Filtro';
import { FiltroAcademico } from '@/components/FiltroAcademico';
import { EstadoFilas, Paginador, Tabla, Td, Th } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { Insignia } from '@/components/ui/insignia';
import { BotonEnlace } from '@/components/ui/boton';
import { api, type Pagina } from '@/lib/api';
import { useAnios } from '@/lib/catalogos';
import { useFiltrosUrl } from '@/lib/hooks';
import { fecha, nombreCompleto } from '@/lib/utils';

interface MatriculaFila {
  id: number;
  estado: string;
  estudiante_id: number;
  anio: number;
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string;
  nivel: string;
  grado_nombre: string;
  seccion: string;
  creado_en: string;
}

export default function ListaMatriculas() {
  const { puede } = useSesion();
  const anios = useAnios();
  const { filtros, pagina, cambiar, hayFiltros, limpiar } = useFiltrosUrl(['q', 'anio', 'nivel_id', 'grado_id', 'seccion_id'] as const);
  const consulta = useQuery({
    queryKey: ['matriculas', filtros, pagina],
    queryFn: () => api.get<Pagina<MatriculaFila>>('/matriculas', { ...filtros, pagina }),
    placeholderData: keepPreviousData,
  });

  return (
    <>
      <Encabezado
        titulo="Matrículas"
        descripcion="Inscripciones por año y grado." total={consulta.data?.meta.total}
        acciones={
          puede('p_matricular') && (
            <BotonEnlace to="/matriculas/nueva">
              <ClipboardPlus aria-hidden /> Nueva matrícula
            </BotonEnlace>
          )
        }
      />

      <BarraFiltros activos={hayFiltros} alLimpiar={limpiar}>
        <Buscador valor={filtros.q} alCambiar={(q) => cambiar({ q })} placeholder="Buscar alumno o DNI" />
        <Filtro etiqueta="Año" valor={filtros.anio} opciones={anios.data?.map((a) => ({ valor: a.anio, texto: String(a.anio) })) ?? []} alCambiar={(anio) => cambiar({ anio })} />
        <FiltroAcademico valores={filtros} alCambiar={cambiar} />
      </BarraFiltros>

      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}

      <Tabla pie={<Paginador meta={consulta.data?.meta} alCambiar={(p) => cambiar({ pagina: p })} />}>
        <thead>
          <tr>
            <Th>Estudiante</Th>
            <Th>Año</Th>
            <Th>Nivel y grado</Th>
            <Th>Registrada</Th>
            <Th>Estado</Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={consulta.isPending} vacio={consulta.data?.data.length === 0} columnas={5} mensaje="No hay matrículas con esos filtros." />
          {consulta.data?.data.map((m) => (
            <tr key={m.id} className="hover:bg-slate-50">
              <Td>
                <Link to={`/estudiantes/${m.estudiante_id}`} className="text-slate-900 hover:text-marca-600">{nombreCompleto(m, true)}</Link>
                <span className="block text-xs text-slate-500">DNI {m.dni}</span>
              </Td>
              <Td className="tabular-nums">{m.anio}</Td>
              <Td className="text-slate-600">{m.nivel} · {m.grado_nombre} {m.seccion}</Td>
              <Td className="text-slate-600">{fecha(m.creado_en)}</Td>
              <Td><Insignia tono={m.estado === 'Activa' ? 'verde' : 'gris'}>{m.estado}</Insignia></Td>
            </tr>
          ))}
        </tbody>
      </Tabla>
    </>
  );
}
