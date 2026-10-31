import Swal from 'sweetalert2';

/**
 * Alertas del sistema (SweetAlert2). Los estilos están en index.css (clases alerta-* y aviso-*).
 * Si hay un <dialog> abierto, la alerta se monta dentro de él: los diálogos nativos
 * viven en la "top layer" y taparían cualquier alerta montada en <body>.
 */
type Tono = 'info' | 'peligro' | 'exito';

// Iconos de lucide (help-circle, alert-triangle, x-circle, check) como SVG en línea.
const svg = (cuerpo: string) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${cuerpo}</svg>`;
const ICONOS = {
  pregunta: svg('<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>'),
  advertencia: svg('<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>'),
  error: svg('<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>'),
  exito: svg('<path d="M20 6 9 17l-5-5"/>'),
};

const destino = () => (document.querySelector('dialog[open]') as HTMLElement | null) ?? 'body';

function clases(tono: Tono, peligro = false) {
  return {
    container: 'alerta-fondo',
    popup: 'alerta',
    icon: `alerta-icono alerta-icono--${tono}`,
    title: 'alerta-titulo',
    htmlContainer: 'alerta-texto',
    actions: 'alerta-acciones',
    confirmButton: `alerta-boton ${peligro ? 'alerta-boton--peligro' : 'alerta-boton--primario'}`,
    cancelButton: 'alerta-boton alerta-boton--secundario',
  };
}

interface OpcionesConfirmar {
  titulo: string;
  texto?: string;
  /** Texto del botón de acción. */
  accion?: string;
  /** Acción destructiva: botón rojo e icono de advertencia. */
  peligro?: boolean;
}

/** Pide confirmación y devuelve true si el usuario aceptó. */
export async function confirmar({ titulo, texto, accion = 'Confirmar', peligro = false }: OpcionesConfirmar): Promise<boolean> {
  const r = await Swal.fire({
    target: destino(),
    title: titulo,
    text: texto,
    icon: peligro ? 'warning' : 'question',
    iconHtml: peligro ? ICONOS.advertencia : ICONOS.pregunta,
    showCancelButton: true,
    cancelButtonText: 'Cancelar',
    confirmButtonText: accion,
    reverseButtons: true,
    buttonsStyling: false,
    customClass: clases(peligro ? 'peligro' : 'info', peligro),
  });
  return r.isConfirmed;
}

/** Aviso breve en la esquina tras una acción correcta. */
export function avisoExito(texto: string) {
  void Swal.fire({
    target: destino(),
    toast: true,
    position: 'top-end',
    icon: 'success',
    iconHtml: ICONOS.exito,
    title: texto,
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    customClass: { popup: 'aviso', icon: 'aviso-icono', title: 'aviso-titulo' },
    didOpen: (el) => {
      el.addEventListener('mouseenter', Swal.stopTimer);
      el.addEventListener('mouseleave', Swal.resumeTimer);
    },
  });
}

/** Error que el usuario debe leer: ventana con botón para cerrar. */
export function avisoError(texto: string, titulo = 'No se pudo completar') {
  void Swal.fire({
    target: destino(),
    icon: 'error',
    iconHtml: ICONOS.error,
    title: titulo,
    text: texto,
    confirmButtonText: 'Entendido',
    buttonsStyling: false,
    customClass: clases('peligro'),
  });
}
