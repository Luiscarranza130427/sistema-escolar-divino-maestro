import { Campo } from '@/components/ui/campo';
import { errorDe } from '@/lib/utils';
import { TITULO_CONTACTO, type DatosContacto, type TipoContacto } from './formulario';

interface Props {
  tipo: TipoContacto;
  datos: DatosContacto;
  alCambiar: (d: DatosContacto) => void;
  apoderado: boolean;
  alMarcarApoderado: () => void;
  error: unknown;
}

export function FormContacto({ tipo, datos, alCambiar, apoderado, alMarcarApoderado, error }: Props) {
  const campo = (clave: keyof DatosContacto) => ({
    value: datos[clave],
    onChange: (e: { target: { value: string } }) => alCambiar({ ...datos, [clave]: e.target.value }),
    error: errorDe(error, `${tipo}.${clave}`),
  });
  const emergencia = tipo === 'emergencia';

  return (
    <fieldset className="rounded-xl border border-slate-200 p-4">
      <legend className="px-1 text-sm font-medium text-slate-900">{TITULO_CONTACTO[tipo]}</legend>
      <label className="mb-3 flex w-fit cursor-pointer items-center gap-2 text-sm text-slate-700">
        <input type="radio" name="apoderado" checked={apoderado} onChange={alMarcarApoderado} className="size-4 accent-marca-600" />
        Apoderado principal
      </label>
      <div className="grid gap-3 sm:grid-cols-2">
        <Campo etiqueta="DNI" inputMode="numeric" maxLength={8} {...campo('dni')} />
        <Campo etiqueta={emergencia ? 'Celular *' : 'Celular'} inputMode="tel" maxLength={9} {...campo('celular')} />
        <div className="sm:col-span-2">
          <Campo etiqueta="Nombres" {...campo('nombres')} />
        </div>
        <Campo etiqueta="Apellido paterno" {...campo('apellido_paterno')} />
        <Campo etiqueta="Apellido materno" {...campo('apellido_materno')} />
        {emergencia && (
          <div className="sm:col-span-2">
            <Campo etiqueta="Parentesco *" placeholder="Abuela, tío, vecino…" {...campo('parentesco')} />
          </div>
        )}
      </div>
      <p className="mt-3 text-xs text-slate-500">Déjalo en blanco si no corresponde. Si el DNI ya existe, se actualizan sus datos.</p>
    </fieldset>
  );
}
