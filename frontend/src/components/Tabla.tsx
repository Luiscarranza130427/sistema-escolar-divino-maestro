import type { ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react';
import { ChevronLeft, ChevronRight, Inbox } from 'lucide-react';
import type { Pagina } from '@/lib/api';
import { cn } from '@/lib/utils';
import { Tarjeta } from './ui/tarjeta';

export function Tabla({ children, pie }: { children: ReactNode; pie?: ReactNode }) {
  return (
    <Tarjeta className="overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">{children}</table>
      </div>
      {pie}
    </Tarjeta>
  );
}

export function Th({ className, ...props }: ThHTMLAttributes<HTMLTableCellElement>) {
  return <th scope="col" className={cn('border-b border-slate-200 bg-slate-50/70 px-4 py-2.5 text-xs font-medium whitespace-nowrap text-slate-500', className)} {...props} />;
}

export function Td({ className, ...props }: TdHTMLAttributes<HTMLTableCellElement>) {
  return <td className={cn('border-b border-slate-100 px-4 py-3 align-middle text-slate-600', className)} {...props} />;
}

/** Filas de relleno mientras carga, o mensaje si no hay resultados. */
export function EstadoFilas({ cargando, vacio, columnas, mensaje = 'No hay resultados.' }: { cargando: boolean; vacio: boolean; columnas: number; mensaje?: string }) {
  if (cargando) {
    return (
      <>
        {Array.from({ length: 5 }, (_, i) => (
          <tr key={i}>
            <td colSpan={columnas} className="border-b border-slate-100 px-4 py-3">
              <div className="h-4 animate-pulse rounded bg-slate-100" />
            </td>
          </tr>
        ))}
      </>
    );
  }
  if (!vacio) return null;
  return (
    <tr>
      <td colSpan={columnas} className="px-4 py-12 text-center text-slate-500">
        <Inbox className="mx-auto mb-2 size-7 text-slate-300" aria-hidden />
        {mensaje}
      </td>
    </tr>
  );
}

export function Paginador({ meta, alCambiar }: { meta?: Pagina<unknown>['meta']; alCambiar: (pagina: number) => void }) {
  if (!meta || meta.total === 0) return null;
  const desde = (meta.pagina - 1) * meta.por_pagina + 1;
  const hasta = Math.min(meta.pagina * meta.por_pagina, meta.total);
  return (
    <div className="flex items-center justify-between gap-4 px-4 py-3 text-sm text-slate-500">
      <span>
        {desde}–{hasta} de {meta.total}
      </span>
      <div className="flex gap-1">
        <button
          onClick={() => alCambiar(meta.pagina - 1)}
          disabled={meta.pagina <= 1}
          className="rounded-lg border border-slate-200 p-1.5 hover:bg-slate-50 disabled:opacity-40"
          aria-label="Página anterior"
        >
          <ChevronLeft className="size-4" />
        </button>
        <button
          onClick={() => alCambiar(meta.pagina + 1)}
          disabled={meta.pagina >= meta.paginas}
          className="rounded-lg border border-slate-200 p-1.5 hover:bg-slate-50 disabled:opacity-40"
          aria-label="Página siguiente"
        >
          <ChevronRight className="size-4" />
        </button>
      </div>
    </div>
  );
}
