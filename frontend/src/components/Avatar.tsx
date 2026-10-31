import { cn } from '@/lib/utils';

const iniciales = (nombre: string) =>
  nombre
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join('');

/** Foto de perfil, o las iniciales del nombre si no tiene. */
export function Avatar({ nombre, foto, className }: { nombre: string; foto: string | null; className?: string }) {
  if (foto) return <img src={foto} alt="" className={cn('size-9 shrink-0 rounded-full object-cover', className)} />;
  return (
    <span aria-hidden className={cn('inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-marca-100 text-sm font-medium text-marca-700', className)}>
      {iniciales(nombre)}
    </span>
  );
}
