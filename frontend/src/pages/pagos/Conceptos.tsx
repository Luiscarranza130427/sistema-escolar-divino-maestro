import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Encabezado } from '@/components/Encabezado';
import { EstadoFilas, Tabla, Td, Th } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { Boton, BotonIcono } from '@/components/ui/boton';
import { Interruptor } from '@/components/ui/interruptor';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import { cn, formato } from '@/lib/utils';
import { FormConcepto } from './FormConcepto';
import { TIPO_COBRO, type Concepto } from './tipos';

export default function Conceptos() {
  const [editando, setEditando] = useState<Concepto | 'nuevo' | null>(null);
  const consulta = useQuery({ queryKey: ['conceptos', 'todos'], queryFn: () => api.get<{ data: Concepto[] }>('/conceptos-pago', { todos: 1 }).then((r) => r.data) });
  const cambiarEstado = useAccion((c: Concepto) => api.patch(`/conceptos-pago/${c.id}/estado`, { activo: !c.activo }), {
    exito: 'Estado del concepto actualizado.',
    invalidar: [['conceptos']],
  });
  const eliminar = useAccion((c: Concepto) => api.delete(`/conceptos-pago/${c.id}`), {
    exito: 'Concepto eliminado.',
    invalidar: [['conceptos']],
  });

  return (
    <>
      <Encabezado
        titulo="Conceptos de pago"
        descripcion="Cobros disponibles al registrar pagos."
        acciones={<Boton onClick={() => setEditando('nuevo')}><Plus className="size-4" aria-hidden /> Nuevo concepto</Boton>}
      />
      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}
      <Tabla>
        <thead>
          <tr>
            <Th className="w-full">Concepto</Th>
            <Th className="text-right">Monto</Th>
            <Th className="pl-10">Estado</Th>
            <Th className="w-px"><span className="sr-only">Acciones</span></Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={consulta.isPending} vacio={consulta.data?.length === 0} columnas={4} mensaje="Aún no hay conceptos de pago." />
          {consulta.data?.map((c) => (
            <tr key={c.id} className="group transition-colors hover:bg-slate-50/70">
              <Td>
                <div className="flex flex-wrap items-center gap-2">
                  <span className={cn(c.activo ? 'text-slate-900' : 'text-slate-400')}>{c.descripcion}</span>
                  <span className={cn('rounded-full px-2 py-0.5 text-xs', c.tipo_cobro === 'familia' ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600')}>{TIPO_COBRO[c.tipo_cobro]}</span>
                </div>
              </Td>
              <Td className={cn('text-right whitespace-nowrap tabular-nums', c.activo ? 'text-slate-900' : 'text-slate-400', !Number(c.monto_default) && 'text-slate-400')}>
                {Number(c.monto_default) ? formato.soles(c.monto_default) : 'Libre'}
              </Td>
              <Td className="pl-10 whitespace-nowrap">
                <div className="flex items-center gap-2.5">
                <Interruptor activo={c.activo} etiqueta={`${c.activo ? 'Desactivar' : 'Activar'} ${c.descripcion}`} disabled={cambiarEstado.isPending} alCambiar={async () => (await confirmar({ titulo: c.activo ? 'Desactivar concepto' : 'Activar concepto', texto: c.activo ? `«${c.descripcion}» dejará de aparecer al registrar pagos.` : `«${c.descripcion}» volverá a aparecer al registrar pagos.`, accion: c.activo ? 'Desactivar' : 'Activar' })) && cambiarEstado.mutate(c)} />
                  <span className={cn('text-sm', c.activo ? 'text-slate-700' : 'text-slate-400')}>{c.activo ? 'Activo' : 'Inactivo'}</span>
                </div>
              </Td>
              <Td className="text-right whitespace-nowrap opacity-70 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                <BotonIcono onClick={() => setEditando(c)} etiqueta={`Editar ${c.descripcion}`}><Pencil /></BotonIcono>
                <BotonIcono peligro onClick={async () => (await confirmar({ titulo: 'Eliminar concepto', texto: `Se eliminará «${c.descripcion}». Si ya tiene pagos registrados no se puede eliminar; desactívalo en su lugar.`, accion: 'Eliminar', peligro: true })) && eliminar.mutate(c)} etiqueta={`Eliminar ${c.descripcion}`}><Trash2 /></BotonIcono>
              </Td>
            </tr>
          ))}
        </tbody>
      </Tabla>

      {editando && <FormConcepto concepto={editando === 'nuevo' ? undefined : editando} alCerrar={() => setEditando(null)} />}
    </>
  );
}
