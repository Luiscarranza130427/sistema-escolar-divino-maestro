import { useEffect, useState, type FormEvent } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router';
import { Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { avisoError, avisoExito } from '@/lib/alertas';
import { ErrorApi } from '@/lib/api';
import { cn } from '@/lib/utils';

/** Fotos del colegio (en frontend/public). Con más de una, rotan cada pocos segundos. */
const fotos = ['/institucion.jpg'];

export default function Login() {
  const { usuario, iniciarSesion } = useSesion();
  const navigate = useNavigate();
  const destino = (useLocation().state as { desde?: string } | null)?.desde ?? '/';
  const [correo, setCorreo] = useState('');
  const [password, setPassword] = useState('');
  const [verPassword, setVerPassword] = useState(false);
  const [enviando, setEnviando] = useState(false);
  const [error, setError] = useState<ErrorApi | null>(null);
  const [fotoActual, setFotoActual] = useState(0);

  useEffect(() => {
    if (fotos.length < 2) return;
    const id = setInterval(() => setFotoActual((i) => (i + 1) % fotos.length), 6000);
    return () => clearInterval(id);
  }, []);

  if (usuario) return <Navigate to={destino} replace />;

  async function enviar(evento: FormEvent) {
    evento.preventDefault();
    setEnviando(true);
    setError(null);
    try {
      const ingresado = await iniciarSesion(correo.trim(), password);
      navigate(destino, { replace: true });
      avisoExito(`¡Bienvenido, ${ingresado.nombres.split(' ')[0]}!`);
    } catch (e) {
      const fallo = e instanceof ErrorApi ? e : new ErrorApi(0, 'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.');
      setError(fallo);
      // Los errores de un campo se muestran bajo el campo; el resto, en una alerta.
      if (!Object.keys(fallo.campos).length) avisoError(fallo.message, 'No pudimos iniciar sesión');
    } finally {
      setEnviando(false);
    }
  }

  return (
    <div className="grid min-h-dvh lg:grid-cols-[1.1fr_1fr]">
      <aside className="relative hidden overflow-hidden bg-slate-900 lg:block">
        {fotos.map((foto, i) => (
          <img
            key={foto}
            src={foto}
            alt=""
            className={cn(
              'absolute inset-0 size-full object-cover transition-opacity duration-1000 motion-reduce:transition-none',
              i === fotoActual ? 'opacity-100' : 'opacity-0',
            )}
          />
        ))}
        <div className="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-black/60 to-transparent" />
        <div className="absolute inset-x-0 bottom-0 flex items-end justify-between p-8 text-white">
          <p className="text-lg font-medium drop-shadow">I.E. Divino Maestro</p>
          {fotos.length > 1 && (
            <div className="flex gap-1.5">
              {fotos.map((foto, i) => (
                <button
                  key={foto}
                  type="button"
                  onClick={() => setFotoActual(i)}
                  aria-label={`Ver foto ${i + 1}`}
                  className={cn('h-1.5 rounded-full bg-white transition-all', i === fotoActual ? 'w-6' : 'w-1.5 opacity-50')}
                />
              ))}
            </div>
          )}
        </div>
      </aside>

      <main className="relative flex items-center justify-center overflow-hidden bg-slate-50 px-4 py-10 sm:px-6">
        <div
          aria-hidden
          className="pointer-events-none absolute -top-32 -right-32 size-96 rounded-full bg-marca-100/70 blur-3xl"
        />
        <div
          aria-hidden
          className="pointer-events-none absolute -bottom-40 -left-24 size-96 rounded-full bg-acento-500/10 blur-3xl"
        />

        <div className="relative w-full max-w-md">
          <div className="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-marca-900/5 sm:p-10">
            <div className="flex flex-col items-center text-center">
              <img
                src="/logo-institucion.jpg"
                alt="Logo de la institución"
                className="size-20 rounded-full object-cover shadow-md ring-4 ring-marca-50"
              />
              <h1 className="mt-5 text-2xl font-medium tracking-tight text-slate-900">Bienvenido</h1>
              <p className="mt-1 text-sm text-slate-500">Ingresa con tu cuenta del colegio para continuar.</p>
            </div>

            <form onSubmit={enviar} className="mt-8 space-y-5" noValidate>
              <Campo
                etiqueta="Correo"
                type="email"
                autoComplete="username"
                autoFocus
                placeholder="nombre@colegio.edu.pe"
                icono={Mail}
                value={correo}
                onChange={(e) => setCorreo(e.target.value)}
                error={error?.campos.correo}
              />
              <Campo
                etiqueta="Contraseña"
                type={verPassword ? 'text' : 'password'}
                autoComplete="current-password"
                placeholder="••••••••"
                icono={Lock}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                error={error?.campos.password}
                accion={
                  <button
                    type="button"
                    onClick={() => setVerPassword((v) => !v)}
                    className="rounded-md p-1 text-slate-400 transition-colors hover:text-slate-600"
                    aria-label={verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                  >
                    {verPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                  </button>
                }
              />
              <Boton type="submit" className="h-11 w-full text-base shadow-md shadow-marca-600/20" cargando={enviando}>
                {enviando ? 'Ingresando…' : 'Ingresar'}
              </Boton>
            </form>
          </div>

          <p className="mt-6 text-center text-xs text-slate-400">
            ¿Problemas para ingresar? Comunícate con la dirección del colegio.
          </p>
        </div>
      </main>
    </div>
  );
}
