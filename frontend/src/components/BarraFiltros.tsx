import type { ReactNode } from 'react';

/** Fila de búsqueda y filtros de un listado; muestra "Limpiar filtros" cuando hay alguno activo. */
export function BarraFiltros({ children, activos = false, alLimpiar }: { children: ReactNode; activos?: boolean; alLimpiar?: () => void }) {
  return (
    <div className="mb-4 flex flex-wrap items-center gap-2">
      {children}
      {activos && alLimpiar && (
        <button type="button" onClick={alLimpiar} className="h-9 px-2 text-sm text-slate-500 underline-offset-4 hover:text-slate-900 hover:underline">
          Limpiar filtros
        </button>
      )}
    </div>
  );
}
