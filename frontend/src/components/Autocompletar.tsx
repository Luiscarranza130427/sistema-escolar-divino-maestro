import { useEffect, useId, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Loader2, Search } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Props<T> {
  etiqueta: string;
  placeholder?: string;
  /** Clave de caché y función que busca en la API con el término escrito. */
  clave: string;
  buscar: (q: string) => Promise<T[]>;
  idDe: (item: T) => number | string;
  textoDe: (item: T) => string;
  detalleDe?: (item: T) => string;
  alElegir: (item: T) => void;
  error?: string;
}

/** Campo que busca en la API mientras se escribe (mínimo 2 caracteres) y deja elegir un resultado. */
export function Autocompletar<T>({ etiqueta, placeholder, clave, buscar, idDe, textoDe, detalleDe, alElegir, error }: Props<T>) {
  const id = useId();
  const [texto, setTexto] = useState('');
  const [termino, setTermino] = useState('');
  const [abierto, setAbierto] = useState(false);
  const [activo, setActivo] = useState(0);

  useEffect(() => {
    const t = setTimeout(() => setTermino(texto.trim()), 300);
    return () => clearTimeout(t);
  }, [texto]);

  const resultados = useQuery({ queryKey: [clave, 'buscar', termino], queryFn: () => buscar(termino), enabled: termino.length >= 2 });
  const lista = resultados.data ?? [];
  const mostrar = abierto && termino.length >= 2;

  const elegir = (item: T) => {
    alElegir(item);
    setTexto('');
    setTermino('');
    setAbierto(false);
  };

  return (
    <div className="relative space-y-1.5">
      <label htmlFor={id} className="block text-sm text-slate-600">{etiqueta}</label>
      <div className="relative">
        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" aria-hidden />
        <input
          id={id}
          role="combobox"
          aria-expanded={mostrar}
          aria-controls={`${id}-lista`}
          aria-invalid={error ? true : undefined}
          autoComplete="off"
          value={texto}
          placeholder={placeholder}
          onChange={(e) => {
            setTexto(e.target.value);
            setAbierto(true);
            setActivo(0);
          }}
          onFocus={() => setAbierto(true)}
          onBlur={() => setTimeout(() => setAbierto(false), 150)}
          onKeyDown={(e) => {
            if (!mostrar || !lista.length) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); setActivo((a) => Math.min(a + 1, lista.length - 1)); }
            if (e.key === 'ArrowUp') { e.preventDefault(); setActivo((a) => Math.max(a - 1, 0)); }
            if (e.key === 'Enter') { e.preventDefault(); elegir(lista[activo]); }
            if (e.key === 'Escape') setAbierto(false);
          }}
          className={cn(
            'h-10 w-full rounded-lg border border-slate-300 bg-white pr-9 pl-9 text-sm shadow-xs focus:border-marca-500 focus:ring-3 focus:ring-marca-100 focus:outline-none',
            error && 'border-red-400',
          )}
        />
        {resultados.isFetching && <Loader2 className="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-slate-400" aria-hidden />}
      </div>
      {error && <p className="text-sm text-red-600">{error}</p>}
      {mostrar && (
        <ul id={`${id}-lista`} role="listbox" className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg">
          {resultados.isPending ? (
            <li className="px-3 py-2 text-slate-500">Buscando…</li>
          ) : lista.length === 0 ? (
            <li className="px-3 py-2 text-slate-500">Sin resultados.</li>
          ) : (
            lista.map((item, i) => (
              <li
                key={idDe(item)}
                role="option"
                aria-selected={i === activo}
                onMouseDown={(e) => { e.preventDefault(); elegir(item); }}
                onMouseEnter={() => setActivo(i)}
                className={cn('cursor-pointer px-3 py-2', i === activo && 'bg-marca-50')}
              >
                <span className="text-slate-900">{textoDe(item)}</span>
                {detalleDe && <span className="block text-xs text-slate-500">{detalleDe(item)}</span>}
              </li>
            ))
          )}
        </ul>
      )}
    </div>
  );
}
