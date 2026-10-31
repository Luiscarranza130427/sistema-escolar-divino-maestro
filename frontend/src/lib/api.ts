/**
 * Cliente de la API (/api/v1). Añade el token Bearer y convierte las respuestas de error
 * en ErrorApi, con los mensajes por campo cuando la API los envía (422).
 */

export interface Pagina<T> {
  data: T[];
  meta: { pagina: number; por_pagina: number; total: number; paginas: number };
}

export class ErrorApi extends Error {
  constructor(
    public readonly estado: number,
    mensaje: string,
    public readonly campos: Record<string, string> = {},
  ) {
    super(mensaje);
  }
}

const CLAVE_TOKEN = 'sistema-escolar.token';
type AlExpirar = () => void;
let alExpirar: AlExpirar = () => {};

export const token = {
  leer: (): string | null => {
    try {
      return sessionStorage.getItem(CLAVE_TOKEN);
    } catch {
      return null;
    }
  },
  guardar: (valor: string | null) => {
    try {
      if (valor) sessionStorage.setItem(CLAVE_TOKEN, valor);
      else sessionStorage.removeItem(CLAVE_TOKEN);
    } catch {
      /* almacenamiento no disponible: la sesión dura lo que dure la pestaña */
    }
  },
  /** Se llama cuando la API responde 401 a una petición autenticada. */
  alExpirar: (fn: AlExpirar) => {
    alExpirar = fn;
  },
};

type Consulta = Record<string, string | number | boolean | null | undefined>;

async function pedir<T>(metodo: string, ruta: string, opciones: { cuerpo?: unknown; consulta?: Consulta } = {}): Promise<T> {
  const url = new URL(`/api/v1${ruta}`, window.location.origin);
  for (const [clave, valor] of Object.entries(opciones.consulta ?? {})) {
    if (valor !== undefined && valor !== null && valor !== '') url.searchParams.set(clave, String(valor));
  }

  const cabeceras: Record<string, string> = { Accept: 'application/json' };
  const actual = token.leer();
  if (actual) cabeceras.Authorization = `Bearer ${actual}`;
  if (opciones.cuerpo !== undefined) cabeceras['Content-Type'] = 'application/json';

  let respuesta: Response;
  try {
    respuesta = await fetch(url, {
      method: metodo,
      headers: cabeceras,
      body: opciones.cuerpo === undefined ? undefined : JSON.stringify(opciones.cuerpo),
    });
  } catch {
    throw new ErrorApi(0, 'No se pudo conectar con el servidor. Revisa tu conexión.');
  }

  if (respuesta.status === 204) return undefined as T;
  const json = await respuesta.json().catch(() => null);

  if (!respuesta.ok) {
    if (respuesta.status === 401 && actual) alExpirar();
    throw new ErrorApi(respuesta.status, json?.error?.mensaje ?? 'Ocurrió un error inesperado.', json?.error?.campos ?? {});
  }
  return json as T;
}

export const api = {
  get: <T>(ruta: string, consulta?: Consulta) => pedir<T>('GET', ruta, { consulta }),
  post: <T>(ruta: string, cuerpo?: unknown) => pedir<T>('POST', ruta, { cuerpo: cuerpo ?? {} }),
  put: <T>(ruta: string, cuerpo: unknown) => pedir<T>('PUT', ruta, { cuerpo }),
  patch: <T>(ruta: string, cuerpo: unknown) => pedir<T>('PATCH', ruta, { cuerpo }),
  delete: <T = void>(ruta: string, consulta?: Consulta) => pedir<T>('DELETE', ruta, { consulta }),
};
