import type { ReactNode } from 'react';
import { formato } from '@/lib/utils';

export function Encabezado({ titulo, descripcion, acciones, total }: { titulo: string; descripcion?: string; acciones?: ReactNode; total?: number }) {
  return (
    <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
      <div className="min-w-0">
        <div className="flex items-center gap-2.5">
          <h1 className="text-2xl font-medium tracking-tight text-slate-900">{titulo}</h1>
          {total !== undefined && <span className="rounded-full bg-slate-200/70 px-2 py-0.5 text-xs font-medium text-slate-600 tabular-nums">{formato.numero(total)}</span>}
        </div>
        {descripcion && <p className="mt-0.5 text-sm text-slate-500">{descripcion}</p>}
      </div>
      {acciones && <div className="flex gap-2">{acciones}</div>}
    </div>
  );
}
