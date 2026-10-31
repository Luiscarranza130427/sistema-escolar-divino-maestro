import { useCallback } from 'react';
import { useSearchParams } from 'react-router';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useAvisos } from './avisos';
import { ErrorApi } from './api';

/** Filtros de un listado guardados en la URL (?q=&pagina=), así sobreviven a recargar o volver atrás. */
export function useFiltrosUrl<T extends string>(claves: readonly T[]) {
  const [params, setParams] = useSearchParams();
  const filtros = Object.fromEntries(claves.map((c) => [c, params.get(c) ?? ''])) as Record<T, string>;
  const pagina = Number(params.get('pagina') ?? '1') || 1;

  const cambiar = useCallback(
    (cambios: Partial<Record<T | 'pagina', string | number>>) => {
      setParams(
        (actual) => {
          const nuevo = new URLSearchParams(actual);
          if (!('pagina' in cambios)) nuevo.delete('pagina');
          for (const [clave, valor] of Object.entries(cambios)) {
            if (valor === '' || valor === undefined || valor === null || (clave === 'pagina' && valor === 1)) nuevo.delete(clave);
            else nuevo.set(clave, String(valor));
          }
          return nuevo;
        },
        { replace: true },
      );
    },
    [setParams],
  );

  const hayFiltros = claves.some((c) => filtros[c] !== '');
  const limpiar = useCallback(() => setParams(new URLSearchParams(), { replace: true }), [setParams]);

  return { filtros, pagina, cambiar, hayFiltros, limpiar };
}

/**
 * Mutación con aviso de éxito o error y recarga de las consultas indicadas.
 * Los errores 422 con campos se dejan al formulario (no se avisan dos veces).
 */
export function useAccion<V, R = unknown>(
  fn: (variables: V) => Promise<R>,
  opciones: { exito?: string | ((r: R) => string); invalidar?: string[][]; alTerminar?: (r: R) => void } = {},
) {
  const queryClient = useQueryClient();
  const avisos = useAvisos();
  return useMutation<R, ErrorApi, V>({
    mutationFn: fn,
    onSuccess: async (resultado) => {
      await Promise.all((opciones.invalidar ?? []).map((queryKey) => queryClient.invalidateQueries({ queryKey })));
      if (opciones.exito) avisos.exito(typeof opciones.exito === 'function' ? opciones.exito(resultado) : opciones.exito);
      opciones.alTerminar?.(resultado);
    },
    onError: (error) => {
      if (!(error instanceof ErrorApi) || !Object.keys(error.campos).length) avisos.error(error.message);
    },
  });
}
