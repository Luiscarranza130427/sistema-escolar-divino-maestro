import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...clases: ClassValue[]): string {
  return twMerge(clsx(clases));
}

const soles = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });
const entero = new Intl.NumberFormat('es-PE');

export const formato = {
  soles: (monto: number | string) => soles.format(Number(monto)),
  numero: (valor: number) => entero.format(valor),
};

const fechaCorta = new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
const fechaHora = new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

/** Fechas de la API: "AAAA-MM-DD" o "AAAA-MM-DD hh:mm:ss.uuu". */
export function fecha(valor: string | null | undefined, conHora = false): string {
  if (!valor) return '—';
  const [dia, hora] = valor.split(/[ T]/);
  const d = new Date(`${dia}T${(hora ?? '00:00:00').slice(0, 8)}`);
  return Number.isNaN(d.getTime()) ? valor : (conHora ? fechaHora : fechaCorta).format(d);
}

export function nombreCompleto(p: { nombres: string; apellido_paterno: string; apellido_materno?: string | null }, apellidosPrimero = false): string {
  const apellidos = [p.apellido_paterno, p.apellido_materno].filter(Boolean).join(' ');
  return apellidosPrimero ? `${apellidos}, ${p.nombres}` : `${p.nombres} ${apellidos}`;
}

export const hoy = () => new Date().toLocaleDateString('en-CA');

/** Error de un campo devuelto por la API (422), p. ej. errorDe(error, 'padre.dni'). */
export function errorDe(error: unknown, campo: string): string | undefined {
  const campos = (error as { campos?: Record<string, string> } | null)?.campos;
  return campos?.[campo];
}
