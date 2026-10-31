import { useEffect, useRef, type ReactNode } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Props {
  abierto: boolean;
  alCerrar: () => void;
  titulo: string;
  descripcion?: string;
  children: ReactNode;
  pie?: ReactNode;
  ancho?: 'sm' | 'md' | 'lg';
}

/** Modal accesible sobre <dialog>: foco atrapado, Escape y fondo para cerrar. */
export function Dialogo({ abierto, alCerrar, titulo, descripcion, children, pie, ancho = 'md' }: Props) {
  const ref = useRef<HTMLDialogElement>(null);

  useEffect(() => {
    const dialogo = ref.current;
    if (!dialogo) return;
    if (abierto && !dialogo.open) dialogo.showModal();
    if (!abierto && dialogo.open) dialogo.close();
  }, [abierto]);

  return (
    <dialog
      ref={ref}
      onClose={alCerrar}
      onClick={(e) => e.target === ref.current && alCerrar()}
      className={cn(
        'm-auto w-[calc(100%-2rem)] rounded-xl bg-white p-0 shadow-xl backdrop:bg-slate-900/50',
        { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-3xl' }[ancho],
      )}
    >
      {abierto && (
        <div className="flex max-h-[85dvh] flex-col">
          <div className="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
              <h2 className="text-base font-medium text-slate-900">{titulo}</h2>
              {descripcion && <p className="mt-0.5 text-sm text-slate-500">{descripcion}</p>}
            </div>
            <button onClick={alCerrar} className="-m-1 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
              <X className="size-5" />
            </button>
          </div>
          <div className="overflow-y-auto px-5 py-4">{children}</div>
          {pie && <div className="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">{pie}</div>}
        </div>
      )}
    </dialog>
  );
}
