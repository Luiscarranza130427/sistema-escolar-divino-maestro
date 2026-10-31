export type Rol = 'asistente' | 'director' | 'hiperadmin';
export type Permiso = 'p_editar' | 'p_matricular' | 'p_cobrar' | 'p_auditoria';

export interface Usuario {
  id: number;
  nombres: string;
  correo: string;
  rol: Rol;
  permisos: Permiso[];
  /** URL pública de la foto de perfil, o null. */
  foto: string | null;
}

export interface Sesion {
  token: string;
  tipo: 'Bearer';
  expira_en: string;
  usuario: Usuario;
}

export interface Metricas {
  estudiantes_activos: number;
  ingresos_mes: number;
  deudas_pendientes: number;
}

export type EstadoAcademico = 'Activo' | 'Retirado' | 'Trasladado';

export interface EstudianteFila {
  id: number;
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string;
  estado_academico: EstadoAcademico;
  matricula_id: number | null;
  nivel: string | null;
  grado: string | null;
  seccion: string | null;
  anio: number | null;
  estado_matricula: string | null;
}

export interface Persona {
  id: number;
  dni: string;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string | null;
}

export interface Analiticas {
  ingresos_mensuales: { mes: string; total: number; pagos: number }[];
  por_nivel: { nivel: string; total: number }[];
  por_grado: { nivel: string; grado: string; total: number }[];
  cobranza: { total: number; pagado: number; saldo: number };
  por_concepto: { concepto: string; total: number; pagos: number }[];
  recientes: { id: number; responsable: string; concepto: string; monto: number; fecha_pago: string }[];
}
