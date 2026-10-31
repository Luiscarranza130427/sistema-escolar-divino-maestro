import { useState, type FormEvent } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { Dialogo } from '@/components/ui/dialogo';
import { api } from '@/lib/api';
import { useAccion } from '@/lib/hooks';
import { errorDe } from '@/lib/utils';

export interface PermisoApi { id: number; codigo: string; nombre: string; descripcion?: string | null }
export interface Asistente { id: number; nombres: string; correo: string; estado: boolean; permisos: PermisoApi[] }

/** Crea un asistente o, si se pasa uno, edita solo sus permisos. */
export function FormAsistente({ asistente, alCerrar }: { asistente?: Asistente; alCerrar: () => void }) {
  const permisos = useQuery({ queryKey: ['permisos'], queryFn: () => api.get<{ data: PermisoApi[] }>('/permisos').then((r) => r.data), staleTime: Infinity });
  const [nombres, setNombres] = useState('');
  const [correo, setCorreo] = useState('');
  const [password, setPassword] = useState('');
  const [elegidos, setElegidos] = useState<number[]>(asistente?.permisos.map((p) => p.id) ?? []);
  const guardar = useAccion(
    () => (asistente ? api.put(`/usuarios/asistentes/${asistente.id}/permisos`, { permisos: elegidos }) : api.post('/usuarios/asistentes', { nombres, correo, password, permisos: elegidos })),
    { exito: asistente ? 'Permisos actualizados.' : 'Asistente creado.', invalidar: [['asistentes']], alTerminar: alCerrar },
  );
  const alternar = (id: number) => setElegidos((e) => (e.includes(id) ? e.filter((x) => x !== id) : [...e, id]));
  const enviar = (e: FormEvent) => {
    e.preventDefault();
    guardar.mutate(undefined);
  };

  return (
    <Dialogo
      abierto
      alCerrar={alCerrar}
      titulo={asistente ? `Permisos de ${asistente.nombres}` : 'Nuevo asistente'}
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton type="submit" form="form-asistente" cargando={guardar.isPending}>Guardar</Boton>
        </>
      }
    >
      <form id="form-asistente" onSubmit={enviar} className="space-y-4">
        {!asistente && (
          <>
            <Campo etiqueta="Nombre completo" maxLength={150} value={nombres} onChange={(e) => setNombres(e.target.value)} error={errorDe(guardar.error, 'nombres')} />
            <Campo etiqueta="Correo" type="email" autoComplete="off" value={correo} onChange={(e) => setCorreo(e.target.value)} error={errorDe(guardar.error, 'correo')} />
            <Campo
              etiqueta="Contraseña inicial"
              type="password"
              autoComplete="new-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              error={errorDe(guardar.error, 'password')}
            />
            <p className="-mt-2 text-xs text-slate-500">Mínimo 8 caracteres. Pide que la cambie al ingresar.</p>
          </>
        )}
        <fieldset>
          <legend className="mb-2 text-sm text-slate-600">Permisos</legend>
          <div className="space-y-2">
            {permisos.data?.map((p) => (
              <label key={p.id} className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                <input type="checkbox" checked={elegidos.includes(p.id)} onChange={() => alternar(p.id)} className="mt-0.5 size-4 accent-marca-600" />
                <span className="text-sm">
                  <span className="text-slate-900">{p.nombre}</span>
                  {p.descripcion && <span className="block text-slate-500">{p.descripcion}</span>}
                </span>
              </label>
            ))}
          </div>
          {errorDe(guardar.error, 'permisos') && <p className="mt-2 text-sm text-red-600">{errorDe(guardar.error, 'permisos')}</p>}
        </fieldset>
      </form>
    </Dialogo>
  );
}
