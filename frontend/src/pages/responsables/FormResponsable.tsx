import { useState, type FormEvent } from 'react';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { Dialogo } from '@/components/ui/dialogo';
import { api } from '@/lib/api';
import { useAccion } from '@/lib/hooks';
import { errorDe } from '@/lib/utils';

export interface DatosResponsable {
  id?: number;
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string;
  direccion: string;
  celular: string;
  nro_tarjeta_apafa: string;
}

interface Props {
  abierto: boolean;
  alCerrar: () => void;
  inicial?: DatosResponsable;
  alGuardar?: (id: number) => void;
}

const vacio: DatosResponsable = { dni: '', nombres: '', apellido_paterno: '', apellido_materno: '', direccion: '', celular: '', nro_tarjeta_apafa: '' };

/** Alta o edición de los datos propios de un familiar (sin tocar sus vínculos). */
export function FormResponsable({ abierto, alCerrar, inicial, alGuardar }: Props) {
  const [datos, setDatos] = useState<DatosResponsable>(inicial ?? vacio);
  const editando = !!inicial?.id;
  const guardar = useAccion(
    () =>
      editando
        ? api.put(`/responsables/${inicial!.id}`, {
            dni: datos.dni,
            nombres: datos.nombres,
            apellido_paterno: datos.apellido_paterno,
            apellido_materno: datos.apellido_materno,
            direccion: datos.direccion,
            celular: datos.celular,
          }).then(() => inicial!.id!)
        : api
            .post<{ data: { id: number } }>('/responsables', {
              dni: datos.dni,
              nombres: datos.nombres,
              apellido_paterno: datos.apellido_paterno,
              apellido_materno: datos.apellido_materno,
              direccion: datos.direccion,
              celular_actual: datos.celular,
              nro_tarjeta_apafa: datos.nro_tarjeta_apafa,
            })
            .then((r) => r.data.id),
    {
      exito: editando ? 'Datos actualizados.' : 'Familiar registrado.',
      invalidar: [['responsables'], ['responsable'], ['estudiante']],
      alTerminar: (id) => {
        alCerrar();
        alGuardar?.(id);
      },
    },
  );
  const campo = (clave: keyof DatosResponsable, api_?: string) => ({
    value: datos[clave] ?? '',
    onChange: (e: { target: { value: string } }) => setDatos((d) => ({ ...d, [clave]: e.target.value })),
    error: errorDe(guardar.error, api_ ?? clave),
  });
  const enviar = (e: FormEvent) => {
    e.preventDefault();
    guardar.mutate(undefined);
  };

  return (
    <Dialogo
      abierto={abierto}
      alCerrar={alCerrar}
      titulo={editando ? 'Editar familiar' : 'Nuevo familiar'}
      descripcion={editando ? undefined : 'Para vincularlo a un estudiante, regístralo desde la matrícula.'}
      pie={
        <>
          <Boton variante="secundario" onClick={alCerrar}>Cancelar</Boton>
          <Boton type="submit" form="form-responsable" cargando={guardar.isPending}>Guardar</Boton>
        </>
      }
    >
      <form id="form-responsable" onSubmit={enviar} className="grid gap-4 sm:grid-cols-2">
        <Campo etiqueta="DNI" inputMode="numeric" maxLength={8} required {...campo('dni')} />
        <Campo etiqueta="Celular" inputMode="tel" maxLength={9} {...campo('celular', editando ? 'celular' : 'celular_actual')} />
        <div className="sm:col-span-2">
          <Campo etiqueta="Nombres" required {...campo('nombres')} />
        </div>
        <Campo etiqueta="Apellido paterno" required {...campo('apellido_paterno')} />
        <Campo etiqueta="Apellido materno" {...campo('apellido_materno')} />
        <div className="sm:col-span-2">
          <Campo etiqueta="Dirección" {...campo('direccion')} />
        </div>
        {!editando && <Campo etiqueta="N.º tarjeta APAFA" {...campo('nro_tarjeta_apafa')} />}
      </form>
    </Dialogo>
  );
}
