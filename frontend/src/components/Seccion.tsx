import type { ReactNode } from 'react';
import { Tarjeta } from './ui/tarjeta';

/** Bloque con título dentro de una ficha. */
export function Seccion({ titulo, acciones, children }: { titulo: string; acciones?: ReactNode; children: ReactNode }) {
  return (
    <Tarjeta>
      <div className="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-3.5">
        <h2 className="text-sm font-medium text-slate-900">{titulo}</h2>
        {acciones}
      </div>
      <div className="p-5">{children}</div>
    </Tarjeta>
  );
}

/** Lista de pares etiqueta / valor. */
export function Datos({ items }: { items: [string, ReactNode][] }) {
  return (
    <dl className="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
      {items.map(([etiqueta, valor]) => (
        <div key={etiqueta}>
          <dt className="text-xs text-slate-500">{etiqueta}</dt>
          <dd className="mt-0.5 text-slate-900">{valor || <span className="font-normal text-slate-400">—</span>}</dd>
        </div>
      ))}
    </dl>
  );
}

export function Cargando() {
  return <div className="h-40 animate-pulse rounded-xl bg-slate-100" />;
}
