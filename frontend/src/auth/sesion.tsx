import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { api, token } from '@/lib/api';
import type { Permiso, Sesion, Usuario } from '@/lib/tipos';

interface ContextoSesion {
  usuario: Usuario | null;
  /** true mientras se valida un token guardado al abrir la app. */
  cargando: boolean;
  iniciarSesion: (correo: string, password: string) => Promise<Usuario>;
  cerrarSesion: () => Promise<void>;
  /** Reemplaza los datos del usuario tras editar su perfil. */
  actualizarUsuario: (usuario: Usuario) => void;
  puede: (permiso: Permiso) => boolean;
  esDirector: boolean;
}

const Contexto = createContext<ContextoSesion | null>(null);

export function ProveedorSesion({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [cargando, setCargando] = useState(() => token.leer() !== null);

  const olvidar = useCallback(() => {
    token.guardar(null);
    setUsuario(null);
    queryClient.clear();
  }, [queryClient]);

  // Un 401 en cualquier petición (token vencido o acceso revocado) cierra la sesión local.
  useEffect(() => token.alExpirar(olvidar), [olvidar]);

  useEffect(() => {
    if (!token.leer()) return;
    api
      .get<{ data: Usuario }>('/auth/yo')
      .then((r) => setUsuario(r.data))
      .catch(() => olvidar())
      .finally(() => setCargando(false));
  }, [olvidar]);

  const iniciarSesion = useCallback(async (correo: string, password: string) => {
    const { data } = await api.post<{ data: Sesion }>('/auth/login', { correo, password, dispositivo: navigator.userAgent.slice(0, 150) });
    token.guardar(data.token);
    setUsuario(data.usuario);
    return data.usuario;
  }, []);

  const cerrarSesion = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } finally {
      olvidar();
    }
  }, [olvidar]);

  const valor = useMemo<ContextoSesion>(() => {
    const esDirector = usuario?.rol === 'director' || usuario?.rol === 'hiperadmin';
    return {
      usuario,
      cargando,
      iniciarSesion,
      cerrarSesion,
      actualizarUsuario: setUsuario,
      esDirector,
      puede: (permiso) => esDirector || (usuario?.permisos.includes(permiso) ?? false),
    };
  }, [usuario, cargando, iniciarSesion, cerrarSesion]);

  return <Contexto.Provider value={valor}>{children}</Contexto.Provider>;
}

export function useSesion(): ContextoSesion {
  const contexto = useContext(Contexto);
  if (!contexto) throw new Error('useSesion debe usarse dentro de <ProveedorSesion>.');
  return contexto;
}
