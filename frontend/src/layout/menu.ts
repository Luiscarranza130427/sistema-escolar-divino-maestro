import type { LucideIcon } from 'lucide-react';
import { BookOpen, ClipboardList, FileClock, GraduationCap, LayoutDashboard, Receipt, Tags, UserCog, Users } from 'lucide-react';
import type { Permiso } from '@/lib/tipos';

export interface ItemMenu {
  ruta: string;
  titulo: string;
  icono: LucideIcon;
  /** Permiso necesario para verlo; sin él lo ve todo el personal. */
  permiso?: Permiso;
  soloDirector?: boolean;
}

export const secciones: { titulo: string; items: ItemMenu[] }[] = [
  {
    titulo: 'General',
    items: [{ ruta: '/', titulo: 'Panel', icono: LayoutDashboard }],
  },
  {
    titulo: 'Gestión',
    items: [
      { ruta: '/estudiantes', titulo: 'Estudiantes', icono: GraduationCap },
      { ruta: '/matriculas', titulo: 'Matrículas', icono: ClipboardList },
      { ruta: '/responsables', titulo: 'Familias', icono: Users },
      { ruta: '/pagos', titulo: 'Pagos', icono: Receipt },
      { ruta: '/conceptos', titulo: 'Conceptos de pago', icono: Tags, permiso: 'p_cobrar' },
      { ruta: '/academico', titulo: 'Notas y cursos', icono: BookOpen, permiso: 'p_matricular' },
    ],
  },
  {
    titulo: 'Dirección',
    items: [
      { ruta: '/asistentes', titulo: 'Asistentes', icono: UserCog, soloDirector: true },
      { ruta: '/auditoria', titulo: 'Auditoría', icono: FileClock, soloDirector: true },
    ],
  },
];
