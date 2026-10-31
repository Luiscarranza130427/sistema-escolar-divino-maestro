import { useRef } from 'react';
import { Camera, Trash2 } from 'lucide-react';
import { useSesion } from '@/auth/sesion';
import { Avatar } from '@/components/Avatar';
import { Boton } from '@/components/ui/boton';
import { Tarjeta } from '@/components/ui/tarjeta';
import { avisoError, confirmar } from '@/lib/alertas';
import { api } from '@/lib/api';
import { useAccion } from '@/lib/hooks';
import type { Usuario } from '@/lib/tipos';

const NOMBRE_ROL = { asistente: 'Asistente', director: 'Dirección', hiperadmin: 'Administrador' } as const;
const MAXIMO = 3 * 1024 * 1024;

const leerComoDataUrl = (archivo: File) =>
  new Promise<string>((resolver, rechazar) => {
    const lector = new FileReader();
    lector.onload = () => resolver(String(lector.result));
    lector.onerror = () => rechazar(new Error('No se pudo leer el archivo.'));
    lector.readAsDataURL(archivo);
  });

export function FotoPerfil() {
  const { usuario, actualizarUsuario } = useSesion();
  const entrada = useRef<HTMLInputElement>(null);
  const subir = useAccion((imagen: string) => api.put<{ data: Usuario }>('/auth/foto', { imagen }), {
    exito: 'Foto de perfil actualizada.',
    alTerminar: (r) => actualizarUsuario(r.data),
  });
  const quitar = useAccion(() => api.delete<{ data: Usuario }>('/auth/foto'), {
    exito: 'Foto de perfil eliminada.',
    alTerminar: (r) => actualizarUsuario(r.data),
  });
  if (!usuario) return null;

  const elegir = async (archivo: File | undefined) => {
    if (entrada.current) entrada.current.value = '';
    if (!archivo) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)) return avisoError('Elige una imagen JPG, PNG o WebP.', 'Formato no válido');
    if (archivo.size > MAXIMO) return avisoError('La imagen pesa más de 3 MB. Elige una más liviana.', 'Imagen muy pesada');
    subir.mutate(await leerComoDataUrl(archivo));
  };
  const error = subir.error?.campos.imagen;

  return (
    <Tarjeta className="flex flex-col items-center px-6 py-8 text-center">
      <div className="relative">
        <Avatar nombre={usuario.nombres} foto={usuario.foto} className="size-28 text-3xl ring-4 ring-white shadow-sm" />
        <button
          type="button"
          onClick={() => entrada.current?.click()}
          disabled={subir.isPending}
          className="absolute right-0 bottom-0 inline-flex size-9 items-center justify-center rounded-full border-2 border-white bg-marca-600 text-white shadow-sm hover:bg-marca-700 disabled:opacity-60"
          title="Cambiar foto"
          aria-label="Cambiar foto"
        >
          <Camera className="size-4" aria-hidden />
        </button>
      </div>
      <h2 className="mt-4 text-lg font-medium text-slate-900">{usuario.nombres}</h2>
      <p className="text-sm text-slate-500">{usuario.correo}</p>
      <span className="mt-2 rounded-full bg-marca-50 px-2.5 py-0.5 text-xs font-medium text-marca-700">{NOMBRE_ROL[usuario.rol]}</span>

      <input ref={entrada} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={(e) => void elegir(e.target.files?.[0])} />
      <div className="mt-6 flex w-full flex-col gap-2">
        <Boton variante="secundario" cargando={subir.isPending} onClick={() => entrada.current?.click()}>
          <Camera aria-hidden /> {usuario.foto ? 'Cambiar foto' : 'Subir foto'}
        </Boton>
        {usuario.foto && (
          <Boton
            variante="fantasma"
            className="text-red-600 hover:bg-red-50"
            cargando={quitar.isPending}
            onClick={async () => (await confirmar({ titulo: 'Quitar foto', texto: 'Se mostrarán tus iniciales en lugar de la foto.', accion: 'Quitar', peligro: true })) && quitar.mutate(undefined)}
          >
            <Trash2 aria-hidden /> Quitar foto
          </Boton>
        )}
      </div>
      {error && <p className="mt-3 text-sm text-red-600">{error}</p>}
      <p className="mt-4 text-xs text-slate-400">JPG, PNG o WebP de hasta 3 MB. Se recorta en cuadrado.</p>
    </Tarjeta>
  );
}
