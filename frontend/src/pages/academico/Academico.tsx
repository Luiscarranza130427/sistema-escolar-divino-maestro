import { useSearchParams } from 'react-router';
import { Encabezado } from '@/components/Encabezado';
import { cn } from '@/lib/utils';
import Cursos from './Cursos';
import Notas from './Notas';
import Periodos from './Periodos';

const PESTANAS = { notas: 'Notas', cursos: 'Cursos', periodos: 'Periodos' } as const;
type Pestana = keyof typeof PESTANAS;

export default function Academico() {
  const [params, setParams] = useSearchParams();
  const tab = params.get('tab') ?? '';
  const actual: Pestana = tab in PESTANAS ? (tab as Pestana) : 'notas';

  return (
    <>
      <Encabezado titulo="Notas y cursos" descripcion="Notas, cursos y periodos." />
      <div role="tablist" className="mb-5 flex gap-1 border-b border-slate-200">
        {(Object.keys(PESTANAS) as Pestana[]).map((p) => (
          <button
            key={p}
            role="tab"
            aria-selected={actual === p}
            onClick={() => setParams(p === 'notas' ? {} : { tab: p }, { replace: true })}
            className={cn('-mb-px border-b-2 px-4 py-2 text-sm font-medium', actual === p ? 'border-marca-600 text-marca-700' : 'border-transparent text-slate-500 hover:text-slate-800')}
          >
            {PESTANAS[p]}
          </button>
        ))}
      </div>
      {actual === 'notas' && <Notas />}
      {actual === 'cursos' && <Cursos />}
      {actual === 'periodos' && <Periodos />}
    </>
  );
}
