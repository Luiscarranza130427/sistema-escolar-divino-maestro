import { useId, type SelectHTMLAttributes } from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Props extends SelectHTMLAttributes<HTMLSelectElement> {
  etiqueta?: string;
  error?: string;
}

/** Select nativo con flecha propia (la del navegador recorta el texto y cambia según el sistema). */
export function Selector({ etiqueta, error, className, id, children, ...props }: Props) {
  const generado = useId();
  const idCampo = id ?? generado;
  return (
    <div className="space-y-1.5">
      {etiqueta && (
        <label htmlFor={idCampo} className="block text-sm text-slate-600">
          {etiqueta}
        </label>
      )}
      <div className="relative">
        <select
          id={idCampo}
          aria-invalid={error ? true : undefined}
          className={cn(
            'h-10 w-full cursor-pointer appearance-none truncate rounded-lg border border-slate-300 bg-white pr-9 pl-3 text-sm text-slate-800 shadow-xs transition-colors',
            'hover:border-slate-400 focus:border-marca-500 focus:ring-3 focus:ring-marca-100 focus:outline-none',
            'disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-400',
            error && 'border-red-400',
            className,
          )}
          {...props}
        >
          {children}
        </select>
        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-400" aria-hidden />
      </div>
      {error && <p className="text-sm text-red-600">{error}</p>}
    </div>
  );
}
