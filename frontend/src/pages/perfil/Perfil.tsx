import { useState, type FormEvent } from 'react';
import { useSesion } from '@/auth/sesion';
import { Encabezado } from '@/components/Encabezado';
import { Seccion } from '@/components/Seccion';
import { Boton } from '@/components/ui/boton';
import { Campo } from '@/components/ui/campo';
import { api } from '@/lib/api';
import { confirmar } from '@/lib/alertas';
import { useAccion } from '@/lib/hooks';
import type { Usuario } from '@/lib/tipos';
import { errorDe } from '@/lib/utils';
import { FotoPerfil } from './FotoPerfil';

export default function Perfil() {
  return (
    <>
      <Encabezado titulo="Mi perfil" descripcion="Tus datos de acceso, tu foto y tu contraseña." />
      <div className="grid gap-6 lg:grid-cols-3">
        <div>
          <FotoPerfil />
        </div>
        <div className="space-y-6 lg:col-span-2">
          <DatosPersonales />
          <CambiarContrasena />
        </div>
      </div>
    </>
  );
}

function DatosPersonales() {
  const { usuario, actualizarUsuario } = useSesion();
  const [nombres, setNombres] = useState(usuario?.nombres ?? '');
  const [correo, setCorreo] = useState(usuario?.correo ?? '');
  const guardar = useAccion(() => api.put<{ data: Usuario }>('/auth/perfil', { nombres, correo }), {
    exito: 'Datos actualizados.',
    alTerminar: (r) => actualizarUsuario(r.data),
  });
  const sinCambios = nombres.trim() === usuario?.nombres && correo.trim() === usuario?.correo;
  const enviar = async (e: FormEvent) => {
    e.preventDefault();
    const cambiaCorreo = correo.trim() !== usuario?.correo;
    if (await confirmar({ titulo: 'Guardar datos', texto: cambiaCorreo ? `Desde ahora ingresarás con ${correo.trim()}.` : 'Se actualizará tu nombre.', accion: 'Guardar' })) guardar.mutate(undefined);
  };

  return (
    <Seccion titulo="Datos personales">
      <form onSubmit={enviar} noValidate className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <Campo etiqueta="Nombres" value={nombres} onChange={(e) => setNombres(e.target.value)} error={errorDe(guardar.error, 'nombres')} />
          <Campo etiqueta="Correo" type="email" autoComplete="email" value={correo} onChange={(e) => setCorreo(e.target.value)} error={errorDe(guardar.error, 'correo')} />
        </div>
        <div className="flex justify-end">
          <Boton type="submit" cargando={guardar.isPending} disabled={sinCambios || !nombres.trim() || !correo.trim()}>
            Guardar datos
          </Boton>
        </div>
      </form>
    </Seccion>
  );
}

function CambiarContrasena() {
  const [actual, setActual] = useState('');
  const [nueva, setNueva] = useState('');
  const [repetida, setRepetida] = useState('');
  const distintas = repetida !== '' && nueva !== repetida;
  const cambiar = useAccion(() => api.put('/auth/password', { password_actual: actual, password_nueva: nueva }), {
    exito: 'Contraseña actualizada. Se cerraron tus otras sesiones.',
    alTerminar: () => {
      setActual('');
      setNueva('');
      setRepetida('');
    },
  });
  const enviar = async (e: FormEvent) => {
    e.preventDefault();
    if (distintas) return;
    if (await confirmar({ titulo: 'Cambiar contraseña', texto: 'Se cerrarán tus sesiones abiertas en otros dispositivos.', accion: 'Cambiar' })) cambiar.mutate(undefined);
  };

  return (
    <Seccion titulo="Contraseña">
      <form onSubmit={enviar} noValidate className="space-y-4">
        <Campo etiqueta="Contraseña actual" type="password" autoComplete="current-password" value={actual} onChange={(e) => setActual(e.target.value)} error={errorDe(cambiar.error, 'password_actual')} />
        <div className="grid gap-4 sm:grid-cols-2">
          <Campo etiqueta="Nueva contraseña" type="password" autoComplete="new-password" value={nueva} onChange={(e) => setNueva(e.target.value)} error={errorDe(cambiar.error, 'password_nueva')} />
          <Campo
            etiqueta="Repite la nueva contraseña"
            type="password"
            autoComplete="new-password"
            value={repetida}
            onChange={(e) => setRepetida(e.target.value)}
            error={distintas ? 'Las contraseñas no coinciden.' : undefined}
          />
        </div>
        <div className="flex items-center justify-between gap-3">
          <p className="text-xs text-slate-500">Mínimo 8 caracteres.</p>
          <Boton type="submit" cargando={cambiar.isPending} disabled={!actual || !nueva || distintas}>
            Cambiar contraseña
          </Boton>
        </div>
      </form>
    </Seccion>
  );
}
