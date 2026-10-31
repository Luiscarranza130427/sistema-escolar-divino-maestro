import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router';
import { Loader2 } from 'lucide-react';
import type { Permiso } from '@/lib/tipos';
import { useSesion } from './sesion';

interface Props {
  children: ReactNode;
  permiso?: Permiso;
  soloDirector?: boolean;
}

/** Exige sesión (y opcionalmente un permiso). La API vuelve a validar todo; esto solo guía la navegación. */
export function RutaPrivada({ children, permiso, soloDirector }: Props) {
  const { usuario, cargando, puede, esDirector } = useSesion();
  const { pathname } = useLocation();

  if (cargando) {
    return (
      <div className="flex min-h-dvh items-center justify-center text-slate-500">
        <Loader2 className="size-6 animate-spin" aria-label="Cargando" />
      </div>
    );
  }
  if (!usuario) return <Navigate to="/login" state={{ desde: pathname }} replace />;
  if ((soloDirector && !esDirector) || (permiso && !puede(permiso))) return <Navigate to="/" replace />;
  return <>{children}</>;
}
