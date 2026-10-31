import { Filtro } from './Filtro';
import { useGrados, useNiveles, useSecciones } from '@/lib/catalogos';

export interface ValoresFiltro {
  nivel_id: string;
  grado_id: string;
  seccion_id: string;
}

/** Nivel → grado → sección en cascada; cada chip aparece al elegir el anterior y cambiar uno limpia los siguientes. */
export function FiltroAcademico({ valores, alCambiar }: { valores: ValoresFiltro; alCambiar: (v: Partial<ValoresFiltro>) => void }) {
  const niveles = useNiveles();
  const grados = useGrados(valores.nivel_id);
  const secciones = useSecciones(valores.grado_id);

  return (
    <>
      <Filtro
        etiqueta="Nivel"
        valor={valores.nivel_id}
        opciones={niveles.data?.map((n) => ({ valor: n.id, texto: n.nombre })) ?? []}
        alCambiar={(v) => alCambiar({ nivel_id: v, grado_id: '', seccion_id: '' })}
      />
      {valores.nivel_id && (
        <Filtro
          etiqueta="Grado"
          valor={valores.grado_id}
          opciones={grados.data?.map((g) => ({ valor: g.id, texto: g.nombre })) ?? []}
          alCambiar={(v) => alCambiar({ grado_id: v, seccion_id: '' })}
        />
      )}
      {valores.grado_id && (
        <Filtro
          etiqueta="Sección"
          valor={valores.seccion_id}
          opciones={secciones.data?.map((s) => ({ valor: s.seccion_id, texto: s.seccion })) ?? []}
          alCambiar={(v) => alCambiar({ seccion_id: v })}
        />
      )}
    </>
  );
}
