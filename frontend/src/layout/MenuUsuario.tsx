import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router';
import { ChevronDown, LogOut, UserRound } from 'lucide-react';
import { Avatar } from '@/components/Avatar';
import { useSesion } from '@/auth/sesion';
import { avisoExito, confirmar } from '@/lib/alertas';
import { cn } from '@/lib/utils';

const NOMBRE_ROL = { asistente: 'Asistente', director: 'Dirección', hiperadmin: 'Administrador' } as const;

/** Usuario en la barra superior: abre un menú con el perfil y la salida. */
export function MenuUsuario() {
  const { usuario, cerrarSesion } = useSesion();
  const [abierto, setAbierto] = useState(false);
  const caja = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!abierto) return;
    const fuera = (e: MouseEvent) => !caja.current?.contains(e.target as Node) && setAbierto(false);
    const escape = (e: KeyboardEvent) => e.key === 'Escape' && setAbierto(false);
    document.addEventListener('mousedown', fuera);
    document.addEventListener('keydown', escape);
    return () => {
      document.removeEventListener('mousedown', fuera);
      document.removeEventListener('keydown', escape);
    };
  }, [abierto]);

  if (!usuario) return null;
  const salir = async () => {
    setAbierto(false);
    if (await confirmar({ titulo: 'Cerrar sesión', texto: 'Tendrás que ingresar de nuevo con tu correo y contraseña.', accion: 'Cerrar sesión' })) {
      await cerrarSesion();
      avisoExito('Sesión cerrada. ¡Hasta pronto!');
    }
  };
  const opcion = 'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-slate-700 hover:bg-slate-100 [&_svg]:size-4 [&_svg]:text-slate-400';

  return (
    <div ref={caja} className="relative">
      <button
        type="button"
        onClick={() => setAbierto((a) => !a)}
        aria-haspopup="menu"
        aria-expanded={abierto}
        className={cn('flex items-center gap-2.5 rounded-full py-1 pr-2.5 pl-1 transition-colors hover:bg-slate-100', abierto && 'bg-slate-100')}
      >
        <Avatar nombre={usuario.nombres} foto={usuario.foto} className="size-8 text-xs" />
        <span className="hidden text-left leading-tight sm:block">
          <span className="block max-w-40 truncate text-sm font-medium text-slate-900">{usuario.nombres}</span>
          <span className="block text-xs text-slate-500">{NOMBRE_ROL[usuario.rol]}</span>
        </span>
        <ChevronDown className={cn('size-4 text-slate-400 transition-transform', abierto && 'rotate-180')} aria-hidden />
      </button>

      {abierto && (
        <div role="menu" className="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-900/5">
          <div className="flex items-center gap-3 px-2.5 pt-2 pb-3">
            <Avatar nombre={usuario.nombres} foto={usuario.foto} />
            <div className="min-w-0 leading-tight">
              <p className="truncate text-sm font-medium text-slate-900">{usuario.nombres}</p>
              <p className="truncate text-xs text-slate-500">{usuario.correo}</p>
            </div>
          </div>
          <div className="border-t border-slate-100 pt-1.5">
            <Link to="/perfil" role="menuitem" onClick={() => setAbierto(false)} className={opcion}>
              <UserRound aria-hidden /> Mi perfil
            </Link>
            <button type="button" role="menuitem" onClick={salir} className={cn(opcion, 'hover:bg-red-50 hover:text-red-600 [&:hover_svg]:text-red-500')}>
              <LogOut aria-hidden /> Cerrar sesión
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
