import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Save } from 'lucide-react';
import { EstadoFilas, Tabla, Td, Th } from '@/components/Tabla';
import { Boton } from '@/components/ui/boton';
import { Selector } from '@/components/ui/selector';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useGrados, useNiveles, useSecciones } from '@/lib/catalogos';
import { useAccion } from '@/lib/hooks';
import { cn, errorDe } from '@/lib/utils';
import { useCursos, usePeriodos } from './tipos';

interface Fila { matricula_id: number; dni: string; estudiante: string; nota: string | null }

const notaValida = (n: string) => n === '' || (/^\d{1,2}(\.\d{1,2})?$/.test(n) && Number(n) <= 20);

export default function Notas() {
  const cursos = useCursos();
  const periodos = usePeriodos();
  const niveles = useNiveles();
  const [cursoId, setCursoId] = useState('');
  const [periodoId, setPeriodoId] = useState('');
  const [nivelId, setNivelId] = useState('');
  const [gradoId, setGradoId] = useState('');
  const [gradoSeccionId, setGradoSeccionId] = useState('');
  const grados = useGrados(nivelId);
  const secciones = useSecciones(gradoId);
  const [notas, setNotas] = useState<Record<number, string>>({});

  const listo = !!cursoId && !!periodoId;
  const filas = useQuery({
    queryKey: ['calificaciones', cursoId, periodoId, gradoSeccionId],
    queryFn: () => api.get<{ data: Fila[] }>('/calificaciones', { curso_id: cursoId, periodo_id: periodoId, grado_seccion_id: gradoSeccionId }).then((r) => r.data),
    enabled: listo,
  });
  useEffect(() => {
    if (filas.data) setNotas(Object.fromEntries(filas.data.map((f) => [f.matricula_id, f.nota === null ? '' : String(Number(f.nota))])));
  }, [filas.data]);

  const guardar = useAccion(() => api.put<{ data: { guardadas: number } }>('/calificaciones', { curso_id: Number(cursoId), periodo_id: Number(periodoId), notas }), {
    exito: (r) => `${r.data.guardadas} nota(s) guardadas.`,
    invalidar: [['calificaciones']],
  });
  const invalidas = Object.values(notas).some((n) => !notaValida(n.trim()));

  return (
    <>
      <div className="mb-4 grid gap-2 rounded-xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgb(15_23_42/0.04)] p-2 sm:grid-cols-2 lg:grid-cols-5">
        <Selector aria-label="Curso" value={cursoId} onChange={(e) => setCursoId(e.target.value)}>
          <option value="">Elige el curso</option>
          {cursos.data?.map((c) => <option key={c.id} value={c.id}>{c.nombre} · {c.grado}</option>)}
        </Selector>
        <Selector aria-label="Periodo" value={periodoId} onChange={(e) => setPeriodoId(e.target.value)}>
          <option value="">Elige el periodo</option>
          {periodos.data?.map((p) => <option key={p.id} value={p.id}>{p.nombre}</option>)}
        </Selector>
        <Selector
          aria-label="Nivel"
          value={nivelId}
          onChange={(e) => {
            setNivelId(e.target.value);
            setGradoId('');
            setGradoSeccionId('');
          }}
        >
          <option value="">Todos los niveles</option>
          {niveles.data?.map((n) => <option key={n.id} value={n.id}>{n.nombre}</option>)}
        </Selector>
        <Selector
          aria-label="Grado"
          value={gradoId}
          disabled={!nivelId}
          onChange={(e) => {
            setGradoId(e.target.value);
            setGradoSeccionId('');
          }}
        >
          <option value="">Todos los grados</option>
          {grados.data?.map((g) => <option key={g.id} value={g.id}>{g.nombre}</option>)}
        </Selector>
        <Selector aria-label="Sección" value={gradoSeccionId} disabled={!gradoId} onChange={(e) => setGradoSeccionId(e.target.value)}>
          <option value="">Todas las secciones</option>
          {secciones.data?.map((s) => <option key={s.grado_seccion_id} value={s.grado_seccion_id}>Sección {s.seccion}</option>)}
        </Selector>
      </div>

      {!listo ? (
        <p className="rounded-xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">Elige un curso y un periodo para registrar las notas.</p>
      ) : (
        <Tabla
          pie={
            !!filas.data?.length && (
              <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <span className="text-sm text-slate-500">Notas de 0 a 20. Las casillas vacías no se guardan.</span>
                <Boton cargando={guardar.isPending} disabled={invalidas} onClick={async () => (await confirmar({ titulo: 'Guardar notas', texto: `Se guardarán ${Object.values(notas).filter((n) => n.trim() !== '').length} nota(s) del curso y periodo elegidos.`, accion: 'Guardar' })) && guardar.mutate(undefined)}>
                  <Save className="size-4" aria-hidden /> Guardar notas
                </Boton>
              </div>
            )
          }
        >
          <thead>
            <tr>
              <Th>Estudiante</Th>
              <Th>DNI</Th>
              <Th className="w-32">Nota</Th>
            </tr>
          </thead>
          <tbody>
            <EstadoFilas cargando={filas.isPending} vacio={filas.data?.length === 0} columnas={3} mensaje="No hay estudiantes matriculados con esos filtros." />
            {filas.data?.map((f) => {
              const valor = notas[f.matricula_id] ?? '';
              const error = !notaValida(valor.trim()) ? 'Nota de 0 a 20' : errorDe(guardar.error, `notas.${f.matricula_id}`);
              return (
                <tr key={f.matricula_id}>
                  <Td className="text-slate-900">{f.estudiante}</Td>
                  <Td className="text-slate-600 tabular-nums">{f.dni}</Td>
                  <Td>
                    <input
                      inputMode="decimal"
                      aria-label={`Nota de ${f.estudiante}`}
                      aria-invalid={error ? true : undefined}
                      title={error}
                      value={valor}
                      onChange={(e) => setNotas((n) => ({ ...n, [f.matricula_id]: e.target.value }))}
                      className={cn(
                        'h-9 w-20 rounded-lg border border-slate-300 px-2 text-center text-sm tabular-nums focus:border-marca-500 focus:ring-3 focus:ring-marca-100 focus:outline-none',
                        error && 'border-red-400 bg-red-50',
                        !error && valor !== '' && Number(valor) < 11 && 'text-red-600',
                      )}
                    />
                  </Td>
                </tr>
              );
            })}
          </tbody>
        </Tabla>
      )}
    </>
  );
}
