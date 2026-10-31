import { useState, type FormEvent } from 'react';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { Dialogo } from '@/components/ui/dialogo';
import { api } from '@/lib/api';
import { useAccion } from '@/lib/hooks';
import { errorDe } from '@/lib/utils';
import type { FichaEstudiante } from './tipos';

export function EditarEstudiante({ estudiante, abierto, alCerrar }: { estudiante: FichaEstudiante['estudiante']; abierto: boolean; alCerrar: () => void }) {
  const [datos, setDatos] = useState({
    dni: estudiante.dni,
    nombres: estudiante.nombres,
    apellido_paterno: estudiante.apellido_paterno,
    apellido_materno: estudiante.apellido_materno,
    fecha_nacimiento: estudiante.fecha_nacimiento ?? '',
  });
  const guardar = useAccion(() => api.put(`/estudiantes/${estudiante.id}`, { ...datos, fecha_nacimiento: datos.fecha_nacimiento || null }), {
    exito: 'Datos del estudiante actualizados.',
    invalidar: [['estudiante', String(estudiante.id)], ['estudiantes']],
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
      abierto={abierto}
      alCerrar={alCerrar}
      titulo="Editar estudiante"
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton type="submit" form="form-estudiante" cargando={guardar.isPending}>Guardar</Boton>
        </>
      }
    >
      <form id="form-estudiante" onSubmit={enviar} className="grid gap-4 sm:grid-cols-2">
        <Campo etiqueta="DNI" inputMode="numeric" maxLength={8} required {...campo('dni')} />
        <Campo etiqueta="Fecha de nacimiento" type="date" {...campo('fecha_nacimiento')} />
        <div className="sm:col-span-2">
          <Campo etiqueta="Nombres" required {...campo('nombres')} />
        </div>
        <Campo etiqueta="Apellido paterno" required {...campo('apellido_paterno')} />
        <Campo etiqueta="Apellido materno" required {...campo('apellido_materno')} />
      </form>
    </Dialogo>
  );
}
