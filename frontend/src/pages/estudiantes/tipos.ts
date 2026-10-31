import type { EstadoAcademico } from '@/lib/tipos';

export interface FichaEstudiante {
  estudiante: {
    id: number;
    dni: string;
    nombres: string;
    apellido_paterno: string;
    apellido_materno: string;
    fecha_nacimiento: string | null;
    estado_academico: EstadoAcademico;
    creado_en: string;
  };
  responsables: {
    relacion_id: number;
    tipo: string;
    parentesco: string | null;
    es_apoderado: boolean;
    id: number;
    dni: string;
    nombres: string;
    apellido_paterno: string;
    apellido_materno: string | null;
    celular: string | null;
    correo: string | null;
    direccion: string | null;
  }[];
  matriculas: { id: number; estado: string; anio: number; nivel: string; grado: string; seccion: string; grado_seccion_id: number }[];
  hermanos: { id: number; dni: string; nombres: string; apellido_paterno: string; apellido_materno: string }[];
  pagos: { id: number; matricula_id: number; fecha_pago: string; monto: string; nro_recibo: string | null; observacion: string | null; concepto: string; estado: string }[];
}
