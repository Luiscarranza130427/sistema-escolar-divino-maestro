import { useId, type ComponentType, type InputHTMLAttributes, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface Props extends InputHTMLAttributes<HTMLInputElement> {
  etiqueta: string;
  error?: string;
  /** Icono decorativo a la izquierda del input. */
  icono?: ComponentType<{ className?: string }>;
  /** Elemento interactivo a la derecha del input (p. ej. mostrar contraseña). */
  accion?: ReactNode;
}

/** Input con etiqueta y mensaje de error accesibles. */
export function Campo({ etiqueta, error, icono: Icono, accion, className, id, ...props }: Props) {
  const generado = useId();
  const idCampo = id ?? generado;
  const idError = `${idCampo}-error`;
  return (
    <div className="space-y-1.5">
      <label htmlFor={idCampo} className="block text-sm text-slate-600">
        {etiqueta}
      </label>
      <div className="relative">
        {Icono && (
          <Icono className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" aria-hidden />
        )}
        <input
          id={idCampo}
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? idError : undefined}
          className={cn(
            'h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-xs placeholder:text-slate-400',
            'focus:border-marca-500 focus:outline-none focus:ring-3 focus:ring-marca-100',
            Icono && 'pl-9',
            accion && 'pr-10',
            error && 'border-red-400 focus:border-red-500 focus:ring-red-100',
            className,
          )}
          {...props}
        />
        {accion && <div className="absolute top-1/2 right-2 -translate-y-1/2">{accion}</div>}
      </div>
      {error && (
        <p id={idError} className="text-sm text-red-600">
          {error}
        </p>
      )}
    </div>
  );
}
