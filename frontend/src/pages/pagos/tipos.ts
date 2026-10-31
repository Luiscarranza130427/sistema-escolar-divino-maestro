export interface Concepto {
  id: number;
  descripcion: string;
  monto_default: string;
  tipo_cobro: 'estudiante' | 'familia';
  activo: boolean;
}

export const TIPO_COBRO: Record<Concepto['tipo_cobro'], string> = {
  estudiante: 'Por estudiante',
  familia: 'Por familia',
};
