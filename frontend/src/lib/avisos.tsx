import { avisoError, avisoExito } from './alertas';

/** Avisos del sistema (SweetAlert2): éxito como aviso breve, error como ventana. */
export function useAvisos() {
  return { exito: avisoExito, error: (t: string) => avisoError(t) };
}
