import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router';
import { Menu, X } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { cn } from '@/lib/utils';
import { MenuUsuario } from './MenuUsuario';
import { secciones } from './menu';

export default function Estructura() {
  const { usuario, puede, esDirector } = useSesion();
  const [abierto, setAbierto] = useState(false);
  const { pathname } = useLocation();

  useEffect(() => setAbierto(false), [pathname]);

  if (!usuario) return null;
  const visibles = secciones
    .map((s) => ({ ...s, items: s.items.filter((i) => (i.soloDirector ? esDirector : !i.permiso || puede(i.permiso))) }))
    .filter((s) => s.items.length > 0);

  const menu = (
    <nav aria-label="Menú principal" className="flex-1 space-y-5 overflow-y-auto px-3 py-4">
      {visibles.map((seccion) => (
        <div key={seccion.titulo}>
          <p className="px-3 pb-1.5 text-[11px] font-medium tracking-wider text-slate-400 uppercase">{seccion.titulo}</p>
          <ul className="space-y-0.5">
            {seccion.items.map(({ ruta, titulo, icono: Icono }) => (
              <li key={ruta}>
                <NavLink
                  to={ruta}
                  end={ruta === '/'}
                  className={({ isActive }) =>
                    cn(
                      'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                      isActive ? 'bg-marca-50 font-medium text-marca-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                    )
                  }
                >
                  {({ isActive }) => (
                    <>
                      {isActive && <span className="absolute inset-y-1.5 -left-3 w-1 rounded-r-full bg-marca-600" aria-hidden />}
                      <Icono className={cn('size-[18px] shrink-0', isActive ? 'text-marca-600' : 'text-slate-400 group-hover:text-slate-600')} aria-hidden />
                      {titulo}
                    </>
                  )}
                </NavLink>
              </li>
            ))}
          </ul>
        </div>
      ))}
    </nav>
  );

  const barra = (
    <div className="flex h-full flex-col border-r border-slate-200 bg-white">
      <div className="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5">
        <img src="/logo-institucion.jpg" alt="" className="size-9 rounded-xl object-cover ring-1 ring-slate-200" />
        <div className="min-w-0 leading-tight">
          <p className="truncate text-sm font-medium text-slate-900">I.E. Divino Maestro</p>
          <p className="text-xs text-slate-500">Sistema escolar</p>
        </div>
      </div>
      {menu}
    </div>
  );

  return (
    <div className="min-h-dvh lg:pl-64">
      <aside className="fixed inset-y-0 left-0 hidden w-64 lg:block">{barra}</aside>

      {abierto && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" onClick={() => setAbierto(false)} aria-hidden />
          <aside className="absolute inset-y-0 left-0 w-72 max-w-[85vw] shadow-xl">{barra}</aside>
          <button onClick={() => setAbierto(false)} className="absolute top-4 right-4 rounded-lg p-2 text-white" aria-label="Cerrar menú">
            <X className="size-5" />
          </button>
        </div>
      )}

      <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur sm:px-6 lg:px-8">
        <button onClick={() => setAbierto(true)} className="-ml-2 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú">
          <Menu className="size-5" />
        </button>
        <img src="/logo-institucion.jpg" alt="" className="size-7 rounded-lg object-cover lg:hidden" />
        <span className="text-sm font-medium text-slate-900 lg:hidden">I.E. Divino Maestro</span>
        <div className="ml-auto">
          <MenuUsuario />
        </div>
      </header>

      <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <Outlet />
      </main>
    </div>
  );
}
