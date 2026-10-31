import type { ReactNode } from 'react';
import { ChevronDown, CirclePlus, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface Opcion {
  valor: string | number;
  texto: string;
}

interface Props {
  etiqueta: string;
  valor: string;
  opciones: Opcion[];
  alCambiar: (valor: string) => void;
  /** Texto cuando no hay valor elegido; si se da, el chip se ve como un filtro fijo (p. ej. "Activos"). */
  porDefecto?: string;
  disabled?: boolean;
  icono?: ReactNode;
}

const base = 'relative inline-flex h-9 items-center gap-1.5 rounded-full border text-sm transition-colors';

/** Filtro en forma de chip: muestra solo la etiqueta y, al elegir, "Etiqueta | valor" resaltado. */
export function Filtro({ etiqueta, valor, opciones, alCambiar, porDefecto, disabled, icono }: Props) {
  const elegida = opciones.find((o) => String(o.valor) === valor);
  const activo = !!elegida;

  return (
    <div
      className={cn(
        base,
        'pl-3',
        activo ? 'border-marca-200 bg-marca-50 pr-1 text-marca-700' : porDefecto ? 'border-slate-200 bg-white pr-3 text-slate-700 shadow-xs' : 'border-dashed border-slate-300 pr-3 text-slate-500',
        disabled ? 'cursor-not-allowed opacity-50' : !activo && 'hover:border-slate-400 hover:bg-white hover:text-slate-800',
      )}
    >
      {!activo && !porDefecto && (icono ?? <CirclePlus className="size-4 shrink-0" aria-hidden />)}
      <span className="whitespace-nowrap">
        {etiqueta}
        {(activo || porDefecto) && (
          <>
            <span className={cn('mx-2', activo ? 'text-marca-200' : 'text-slate-200')}>|</span>
            <span className="font-medium">{elegida?.texto ?? porDefecto}</span>
          </>
        )}
      </span>
      {!activo && porDefecto && <ChevronDown className="size-3.5 text-slate-400" aria-hidden />}
      <select
        aria-label={etiqueta}
        value={valor}
        disabled={disabled}
        onChange={(e) => alCambiar(e.target.value)}
        className="absolute inset-0 cursor-pointer opacity-0 disabled:cursor-not-allowed"
      >
        <option value="">{porDefecto ?? 'Todos'}</option>
        {opciones.map((o) => (
          <option key={o.valor} value={o.valor}>
            {o.texto}
          </option>
        ))}
      </select>
      {activo && (
        <button
          type="button"
          onClick={() => alCambiar('')}
          className="relative z-10 inline-flex size-7 items-center justify-center rounded-full text-marca-500 hover:bg-marca-100 hover:text-marca-700"
          aria-label={`Quitar filtro ${etiqueta}`}
        >
          <X className="size-3.5" aria-hidden />
        </button>
      )}
    </div>
  );
}

/** Chip con un selector de fecha nativo. */
export function FiltroFecha({ etiqueta, valor, alCambiar }: { etiqueta: string; valor: string; alCambiar: (valor: string) => void }) {
  const activo = valor !== '';
  return (
    <label
      className={cn(
        base,
        'cursor-pointer px-3',
        activo ? 'border-marca-200 bg-marca-50 text-marca-700' : 'border-dashed border-slate-300 text-slate-500 hover:border-slate-400 hover:bg-white hover:text-slate-800',
      )}
    >
      <span className="whitespace-nowrap">{etiqueta}</span>
      <input type="date" value={valor} onChange={(e) => alCambiar(e.target.value)} className="w-[8.5rem] cursor-pointer bg-transparent text-sm font-medium focus:outline-none" />
    </label>
  );
}
