import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { KeyRound, Plus, RotateCcw, UserX } from 'lucide-react';
import { Encabezado } from '@/components/Encabezado';
import { EstadoFilas, Tabla, Td, Th } from '@/components/Tabla';
import { AlertaError } from '@/components/ui/alerta';
import { Insignia } from '@/components/ui/insignia';
import { Boton, BotonIcono } from '@/components/ui/boton';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import { FormAsistente, type Asistente } from './FormAsistente';

export default function Asistentes() {
  const [editando, setEditando] = useState<Asistente | 'nuevo' | null>(null);
  const consulta = useQuery({ queryKey: ['asistentes'], queryFn: () => api.get<{ data: Asistente[] }>('/usuarios/asistentes').then((r) => r.data) });
  const revocar = useAccion((a: Asistente) => api.post(`/usuarios/asistentes/${a.id}/revocar`), {
    exito: 'Acceso revocado.',
    invalidar: [['asistentes']],
  });
  const restaurar = useAccion((a: Asistente) => api.post(`/usuarios/asistentes/${a.id}/restaurar`), { exito: 'Acceso restaurado.', invalidar: [['asistentes']] });

  return (
    <>
      <Encabezado
        titulo="Asistentes"
        descripcion="Personal de secretaría y sus permisos."
        acciones={
          <Boton onClick={() => setEditando('nuevo')}>
            <Plus className="size-4" aria-hidden /> Nuevo asistente
          </Boton>
        }
      />
      {consulta.error && <AlertaError>{consulta.error.message}</AlertaError>}
      <Tabla>
        <thead>
          <tr>
            <Th>Asistente</Th>
            <Th>Permisos</Th>
            <Th>Acceso</Th>
            <Th className="w-px"><span className="sr-only">Acciones</span></Th>
          </tr>
        </thead>
        <tbody>
          <EstadoFilas cargando={consulta.isPending} vacio={consulta.data?.length === 0} columnas={4} mensaje="Aún no hay asistentes." />
          {consulta.data?.map((a) => (
            <tr key={a.id}>
              <Td>
                <span className="text-slate-900">{a.nombres}</span>
                <span className="block text-xs text-slate-500">{a.correo}</span>
              </Td>
              <Td>
                <div className="flex flex-wrap gap-1">
                  {a.permisos.length ? a.permisos.map((p) => <Insignia key={p.id}>{p.nombre}</Insignia>) : <span className="text-sm text-slate-400">Solo consulta</span>}
                </div>
              </Td>
              <Td>
                <Insignia tono={a.estado ? 'verde' : 'rojo'}>{a.estado ? 'Activo' : 'Revocado'}</Insignia>
              </Td>
              <Td className="text-right whitespace-nowrap">
                <BotonIcono onClick={() => setEditando(a)} etiqueta={`Permisos de ${a.nombres}`}>
                  <KeyRound />
                </BotonIcono>
                {a.estado ? (
                  <BotonIcono peligro onClick={async () => (await confirmar({ titulo: 'Revocar acceso', texto: `${a.nombres} no podrá ingresar y se cerrarán sus sesiones abiertas. Puedes restaurarlo después.`, accion: 'Revocar', peligro: true })) && revocar.mutate(a)} etiqueta={`Revocar acceso de ${a.nombres}`}>
                    <UserX />
                  </BotonIcono>
                ) : (
                  <BotonIcono cargando={restaurar.isPending && restaurar.variables?.id === a.id} onClick={async () => (await confirmar({ titulo: 'Restaurar acceso', texto: `${a.nombres} podrá volver a ingresar con su contraseña.`, accion: 'Restaurar' })) && restaurar.mutate(a)} etiqueta={`Restaurar acceso de ${a.nombres}`}>
                    <RotateCcw />
                  </BotonIcono>
                )}
              </Td>
            </tr>
          ))}
        </tbody>
      </Tabla>
      {editando && <FormAsistente asistente={editando === 'nuevo' ? undefined : editando} alCerrar={() => setEditando(null)} />}
    </>
  );
}
