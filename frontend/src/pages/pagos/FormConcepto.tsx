import { useState, type FormEvent } from 'react';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { Dialogo } from '@/components/ui/dialogo';
import { Selector } from '@/components/ui/selector';
import { api } from '@/lib/api';
import { useAccion } from '@/lib/hooks';
import { errorDe } from '@/lib/utils';
import { TIPO_COBRO, type Concepto } from './tipos';

export function FormConcepto({ concepto, alCerrar }: { concepto?: Concepto; alCerrar: () => void }) {
  const [descripcion, setDescripcion] = useState(concepto?.descripcion ?? '');
  const [monto, setMonto] = useState(concepto ? String(Number(concepto.monto_default)) : '');
  const [tipo, setTipo] = useState<Concepto['tipo_cobro']>(concepto?.tipo_cobro ?? 'estudiante');
  const guardar = useAccion(
    () => {
      const cuerpo = { descripcion, monto, tipo_cobro: tipo };
      return concepto ? api.put(`/conceptos-pago/${concepto.id}`, cuerpo) : api.post('/conceptos-pago', cuerpo);
    },
    { exito: concepto ? 'Concepto actualizado.' : 'Concepto creado.', invalidar: [['conceptos']], alTerminar: alCerrar },
  );
  const enviar = (e: FormEvent) => {
    e.preventDefault();
    guardar.mutate(undefined);
  };

  return (
    <Dialogo
      abierto
      alCerrar={alCerrar}
      titulo={concepto ? 'Editar concepto' : 'Nuevo concepto'}
      ancho="sm"
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton type="submit" form="form-concepto" cargando={guardar.isPending}>Guardar</Boton>
        </>
      }
    >
      <form id="form-concepto" onSubmit={enviar} className="space-y-4">
        <Campo etiqueta="Concepto" maxLength={80} placeholder="Cuota APAFA 2026" value={descripcion} onChange={(e) => setDescripcion(e.target.value)} error={errorDe(guardar.error, 'descripcion')} />
        <Campo etiqueta="Monto (S/)" inputMode="decimal" placeholder="0.00" value={monto} onChange={(e) => setMonto(e.target.value)} error={errorDe(guardar.error, 'monto')} />
        <Selector etiqueta="Se cobra" value={tipo} onChange={(e) => setTipo(e.target.value as Concepto['tipo_cobro'])} error={errorDe(guardar.error, 'tipo_cobro')}>
          {Object.entries(TIPO_COBRO).map(([valor, texto]) => <option key={valor} value={valor}>{texto}</option>)}
        </Selector>
        {concepto && <p className="text-xs text-slate-500">El nuevo monto se aplica a las cuentas que se abran desde ahora.</p>}
      </form>
    </Dialogo>
  );
}
