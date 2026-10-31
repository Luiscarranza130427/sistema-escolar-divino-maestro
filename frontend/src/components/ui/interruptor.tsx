import { cn } from '@/lib/utils';

/** Interruptor encendido/apagado (role="switch"). */
export function Interruptor({ activo, etiqueta, alCambiar, disabled }: { activo: boolean; etiqueta: string; alCambiar: () => void; disabled?: boolean }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={activo}
      aria-label={etiqueta}
      title={etiqueta}
      disabled={disabled}
      onClick={alCambiar}
      className={cn(
        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full align-middle transition-colors focus-visible:ring-3 focus-visible:ring-marca-100 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60',
        activo ? 'bg-marca-600' : 'bg-slate-300',
      )}
    >
      <span className={cn('absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow-sm transition-transform', activo && 'translate-x-4')} />
    </button>
  );
}
