import { useState, type FormEvent } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { EstadoFilas, Tabla, Td, Th } from '@/components/Tabla';
import { Campo } from '@/components/ui/campo';
import { Dialogo } from '@/components/ui/dialogo';
import { Boton, BotonIcono } from '@/components/ui/boton';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import { errorDe, fecha } from '@/lib/utils';
import { usePeriodos, type Periodo } from './tipos';

export default function Periodos() {
  const periodos = usePeriodos();
  const [editando, setEditando] = useState<Periodo | 'nuevo' | null>(null);
  const desactivar = useAccion((p: Periodo) => api.delete(`/periodos/${p.id}`), { exito: 'Periodo desactivado.', invalidar: [['periodos']] });

  return (
    <>
      <div className="mb-4 flex justify-end">
        <Boton onClick={() => setEditando('nuevo')}><Plus className="size-4" aria-hidden /> Nuevo periodo</Boton>
      </div>
      <Tabla>
        <thead>
          <tr>
            <Th>Periodo</Th>
            <Th>Inicio</Th>
            <Th>Fin</Th>
            <Th className="w-px"><span className="sr-only">Acciones</span></Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={periodos.isPending} vacio={periodos.data?.length === 0} columnas={4} mensaje="Aún no hay periodos académicos." />
          {periodos.data?.map((p) => (
            <tr key={p.id}>
              <Td className="text-slate-900">{p.nombre}</Td>
              <Td className="text-slate-600">{fecha(p.fecha_inicio)}</Td>
              <Td className="text-slate-600">{fecha(p.fecha_fin)}</Td>
              <Td className="text-right whitespace-nowrap">
                <BotonIcono onClick={() => setEditando(p)} etiqueta={`Editar ${p.nombre}`}><Pencil /></BotonIcono>
                <BotonIcono peligro onClick={async () => (await confirmar({ titulo: 'Desactivar periodo', texto: `${p.nombre} dejará de aparecer para registrar notas. Las notas ya registradas se conservan.`, accion: 'Desactivar', peligro: true })) && desactivar.mutate(p)} etiqueta={`Desactivar ${p.nombre}`}><Trash2 /></BotonIcono>
              </Td>
            </tr>
          ))}
        </tbody>
      </Tabla>
      {editando && <FormPeriodo periodo={editando === 'nuevo' ? undefined : editando} alCerrar={() => setEditando(null)} />}
    </>
  );
}

function FormPeriodo({ periodo, alCerrar }: { periodo?: Periodo; alCerrar: () => void }) {
  const [datos, setDatos] = useState({ nombre: periodo?.nombre ?? '', fecha_inicio: periodo?.fecha_inicio?.slice(0, 10) ?? '', fecha_fin: periodo?.fecha_fin?.slice(0, 10) ?? '' });
  const guardar = useAccion(() => (periodo ? api.put(`/periodos/${periodo.id}`, datos) : api.post('/periodos', datos)), {
    exito: periodo ? 'Periodo actualizado.' : 'Periodo creado.',
    invalidar: [['periodos']],
    alTerminar: alCerrar,
  });
  const campo = (clave: keyof typeof datos) => ({
    value: datos[clave],
    onChange: (e: { target: { value: string } }) => setDatos((d) => ({ ...d, [clave]: e.target.value })),
    error: errorDe(guardar.error, clave),
  });
  const enviar = (e: FormEvent) => {
    e.preventDefault();
    guardar.mutate(undefined);
  };
  return (
    <Dialogo
      abierto
      alCerrar={alCerrar}
      titulo={periodo ? 'Editar periodo' : 'Nuevo periodo'}
      ancho="sm"
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton type="submit" form="form-periodo" cargando={guardar.isPending}>Guardar</Boton>
        </>
      }
    >
      <form id="form-periodo" onSubmit={enviar} className="space-y-4">
        <Campo etiqueta="Nombre" maxLength={50} placeholder="I Bimestre" {...campo('nombre')} />
        <div className="grid grid-cols-2 gap-3">
          <Campo etiqueta="Inicio" type="date" {...campo('fecha_inicio')} />
          <Campo etiqueta="Fin" type="date" {...campo('fecha_fin')} />
        </div>
      </form>
    </Dialogo>
  );
}
