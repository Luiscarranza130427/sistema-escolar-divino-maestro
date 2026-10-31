export type TipoContacto = 'padre' | 'madre' | 'emergencia';
export const TIPOS: TipoContacto[] = ['padre', 'madre', 'emergencia'];
export const TITULO_CONTACTO: Record<TipoContacto, string> = { padre: 'Padre', madre: 'Madre', emergencia: 'Contacto de emergencia' };

export interface DatosContacto {
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string;
  celular: string;
  parentesco: string;
}

export const contactoVacio = (): DatosContacto => ({ dni: '', nombres: '', apellido_paterno: '', apellido_materno: '', celular: '', parentesco: '' });

export const tieneDatos = (c: DatosContacto) => Object.values(c).some((v) => v.trim() !== '');

/** Contacto tal como lo devuelve /estudiantes/{id}/datos-matricula. */
export interface ContactoApi {
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  celular: string | null;
  parentesco: string | null;
  es_apoderado: boolean;
}

export interface DatosMatricula {
  estudiante: { id: number; dni: string; nombres: string; apellido_paterno: string; apellido_materno: string };
  familia: Record<TipoContacto, ContactoApi | null>;
}

export function desdeApi(c: ContactoApi | null): DatosContacto {
  if (!c) return contactoVacio();
  return {
    dni: c.dni ?? '',
    nombres: c.nombres ?? '',
    apellido_paterno: c.apellido_paterno ?? '',
    apellido_materno: c.apellido_materno ?? '',
    celular: c.celular ?? '',
    parentesco: c.parentesco ?? '',
  };
}
