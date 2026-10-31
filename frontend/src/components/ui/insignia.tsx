import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const tonos = {
  gris: 'bg-slate-100 text-slate-700',
  verde: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  ambar: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  rojo: 'bg-red-50 text-red-700 ring-red-600/20',
  azul: 'bg-marca-50 text-marca-700 ring-marca-600/20',
} as const;

export type Tono = keyof typeof tonos;

export function Insignia({ tono = 'gris', children }: { tono?: Tono; children: ReactNode }) {
  return <span className={cn('inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-transparent ring-inset', tonos[tono])}>{children}</span>;
}
