import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { Link, type LinkProps } from 'react-router';
import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

const variantes = {
  primario: 'bg-marca-600 text-white shadow-xs hover:bg-marca-700 disabled:bg-marca-600/60',
  secundario: 'border border-slate-300 bg-white text-slate-700 shadow-xs hover:bg-slate-50 disabled:opacity-60',
  fantasma: 'text-slate-600 hover:bg-slate-100 disabled:opacity-60',
  peligro: 'bg-red-600 text-white shadow-xs hover:bg-red-700 disabled:bg-red-600/60',
  peligroSuave: 'border border-red-200 bg-white text-red-600 shadow-xs hover:bg-red-50 disabled:opacity-60',
} as const;

export type Variante = keyof typeof variantes;

const clasesBase = 'inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium whitespace-nowrap transition-colors disabled:cursor-not-allowed [&_svg]:size-4 [&_svg]:shrink-0';

interface Props extends ButtonHTMLAttributes<HTMLButtonElement> {
  variante?: Variante;
  cargando?: boolean;
}

export function Boton({ variante = 'primario', cargando = false, className, children, disabled, type = 'button', ...props }: Props) {
  return (
    <button type={type} className={cn(clasesBase, variantes[variante], className)} disabled={disabled || cargando} {...props}>
      {cargando && <Loader2 className="animate-spin" aria-hidden />}
      {children}
    </button>
  );
}

/** Enlace con aspecto de botón (navegar a otra pantalla). */
export function BotonEnlace({ variante = 'primario', className, ...props }: LinkProps & { variante?: Variante }) {
  return <Link className={cn(clasesBase, variantes[variante], className)} {...props} />;
}

interface PropsIcono extends ButtonHTMLAttributes<HTMLButtonElement> {
  /** Texto del tooltip y del lector de pantalla. */
  etiqueta: string;
  peligro?: boolean;
  cargando?: boolean;
  children: ReactNode;
}

/** Botón cuadrado solo con icono, para las acciones de cada fila. */
export function BotonIcono({ etiqueta, peligro = false, cargando = false, className, children, disabled, ...props }: PropsIcono) {
  return (
    <button
      type="button"
      title={etiqueta}
      aria-label={etiqueta}
      disabled={disabled || cargando}
      className={cn(
        'inline-flex size-8 items-center justify-center rounded-lg text-slate-500 transition-colors disabled:cursor-not-allowed disabled:opacity-50 [&_svg]:size-4',
        peligro ? 'hover:bg-red-50 hover:text-red-600' : 'hover:bg-slate-100 hover:text-slate-900',
        className,
      )}
      {...props}
    >
      {cargando ? <Loader2 className="animate-spin" aria-hidden /> : children}
    </button>
  );
}

/** Grupo de acciones al final de una fila. */
export function AccionesFila({ children }: { children: ReactNode }) {
  return <div className="flex items-center justify-end gap-1">{children}</div>;
}
