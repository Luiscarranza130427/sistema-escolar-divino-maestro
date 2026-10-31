import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Boton } from '@/components/ui/boton';
import { Dialogo } from '@/components/ui/dialogo';
import { Selector } from '@/components/ui/selector';
import { api } from '@/lib/api';
import { useAccion } from '@/lib/hooks';
import { errorDe, nombreCompleto } from '@/lib/utils';

interface Candidato { id: number; nombres: string; apellido_paterno: string; apellido_materno: string | null; tipo: string; parentesco: string | null }

interface Props {
  responsableId: number;
  estudiante: { id: number; nombre: string; es_apoderado: boolean };
  alCerrar: () => void;
}

/** Quita el vínculo con un estudiante; si era el apoderado principal, pide quién lo reemplaza. */
export function Desvincular({ responsableId, estudiante, alCerrar }: Props) {
  const [reemplazo, setReemplazo] = useState('');
  const candidatos = useQuery({
    queryKey: ['candidatos-apoderado', responsableId, estudiante.id],
    queryFn: () => api.get<{ data: Candidato[] }>(`/responsables/${responsableId}/candidatos-apoderado`, { estudiante_id: estudiante.id }).then((r) => r.data),
    enabled: estudiante.es_apoderado,
  });
  const quitar = useAccion(() => api.delete(`/responsables/${responsableId}/estudiantes/${estudiante.id}`, { reemplazo_id: reemplazo || undefined }), {
    exito: 'Vínculo eliminado.',
    invalidar: [['responsable'], ['responsables'], ['estudiante']],
    alTerminar: alCerrar,
  });

  return (
    <Dialogo
      abierto
      alCerrar={alCerrar}
      titulo="Quitar de la familia"
      ancho="sm"
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton variante="peligro" cargando={quitar.isPending} disabled={estudiante.es_apoderado && !reemplazo} onClick={() => quitar.mutate(undefined)}>
            Quitar
          </Boton>
        </>
      }
    >
      <p className="text-sm text-slate-600">
        Dejará de figurar como familiar de <strong>{estudiante.nombre}</strong>. Sus datos y pagos se conservan.
      </p>
      {estudiante.es_apoderado && (
        <div className="mt-4">
          {candidatos.data?.length === 0 ? (
            <p className="text-sm text-amber-700">Es el apoderado y no hay otro familiar que lo reemplace. Registra primero a otro familiar desde la matrícula.</p>
          ) : (
            <Selector etiqueta="Nuevo apoderado principal" value={reemplazo} onChange={(e) => setReemplazo(e.target.value)} error={errorDe(quitar.error, 'reemplazo_id')}>
              <option value="">Elige quién lo reemplaza</option>
              {candidatos.data?.map((c) => (
                <option key={c.id} value={c.id}>{nombreCompleto(c)} ({c.parentesco ?? c.tipo})</option>
              ))}
            </Selector>
          )}
        </div>
      )}
    </Dialogo>
  );
}
